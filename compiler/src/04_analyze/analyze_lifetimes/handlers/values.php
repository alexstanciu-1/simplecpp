<?php
declare(strict_types=1);

/*
 * Role: Evaluate temporary lifetimes in expression order.
 * Used by: Lifetime_Worker (private trait methods on this owner)
 * Call map:
 *   evaluate()
 *     -> Expression_Order::steps(); [action] consume inputs and produce values
 */

namespace analyze_lifetimes;

use check_bodies\value_kind;
use check_bodies\conversion_kind;

// Private methods composed only by this process's callable worker.
trait Value_Lifetimes
{
    /**
     * Follow the checked expression's argument graph, never syntax. Calls finish
     * in the checked segment order; arguments are evaluated left to right and
     * remain live until their consumer call. Explicit cursors bound PHP stack use.
     */
    private function evaluate(int $value_id, int $statement_id, int &$next_call, int $call_limit): void
    {
        foreach (\check_bodies\Expression_Order::steps($this->body, $value_id, $next_call, $call_limit) as $step)
        {
            if ($step->call_id !== 0) {
                $this->consume_call_arguments($step->call_id, $statement_id);
                ++$next_call;
            }
            elseif ($step->value_id !== 0)
            {
                match ($this->body->values[$step->value_id - 1]->kind) {
                    value_kind::conversion => $this->consume_conversion_input($step->value_id, $statement_id),
                    value_kind::operation => $this->consume_operation_inputs($step->value_id, $statement_id),
                    value_kind::local_read, value_kind::local_borrow => $this->consume_indices($step->value_id, $statement_id),
                    // Literal values have no operands.
                    default => null,
                };
            }

            // An expression result becomes available only after all of its inputs were consumed.
            if ($step->value_id !== 0) {
                $value = $this->body->values[$step->value_id - 1];
                // Producing a storage read does not select copy construction; the consumer selects its permission.
                $this->contract($value->type_id, $value->source_node_id, false);
                $this->produce_value($step->value_id);
            }
        }
    }

    /** Index operands end after locating storage; the owning local remains live. */
    private function consume_indices(int $id, int $statement): void
    {
        foreach ($this->body->values[$id - 1]->payload->indices() as $projection) {
            $this->end_value($projection->operand, $statement, lifetime_end::index_input, $id);
        }
    }

    /** End argument access according to passing mode while preserving owned temporaries until expression cleanup. */
    private function consume_call_arguments(int $call_id, int $statement_id): void
    {
        $call = $this->body->calls[$call_id - 1];
        for ($position = 1; $position <= $call->argument_count; ++$position)
        {
            $argument = $this->body->argument_for($call_id, $position);
            $value = $this->body->values[$argument->value_id - 1];
            if ($value->type_id !== $argument->parameter_type_id) {
                $this->fail($value->source_node_id, 'Unsupported lifetime analysis for argument conversion');
            }
            if ($argument->passing !== $this->body->signature_for($call->target_callable_id)->parameter_passing[$position - 1]) {
                throw new \LogicException('Argument passing differs from its checked signature');
            }
            if (($argument->passing === \type_model\argument_passing::borrow_mutable)
                && (($value->kind !== value_kind::local_borrow)
                    || ($this->body->local_passing($value->payload->local_id) === \type_model\argument_passing::borrow_const))) {
                throw new \LogicException('Mutable argument requires writable local storage');
            }
            $borrow = $argument->passing !== \type_model\argument_passing::value;
            $this->contract($argument->parameter_type_id, $value->source_node_id, !$borrow);

            // Argument access ends here; the owned temporary survives through the full expression.
            $this->end_value($argument->value_id, $statement_id,
                $borrow ? lifetime_end::argument_borrow : lifetime_end::argument_copy, $call_id);
        }
    }

    private function consume_conversion_input(int $id, int $statement_id): void
    {
        $conversion = $this->body->conversion_for($id);
        if ($conversion->operation !== conversion_kind::integer_widen) {
            throw new \LogicException('Unsupported lifetime conversion operation');
        }
        $this->end_value($conversion->input_value_id, $statement_id, lifetime_end::conversion_input, $id);
    }

    private function consume_operation_inputs(int $id, int $statement_id): void
    {
        $operation = $this->body->operation_for($id);
        foreach ([$operation->left, $operation->right] as $operand) {
            $this->end_value($operand, $statement_id, lifetime_end::operation_input, $id);
        }
    }

    /** Register a checked value once; validate local liveness and track owned temporary construction order. */
    private function produce_value(int $id): void
    {
        if ((isset($this->live_values[$id])) || (isset($this->values[$id]))) {
            throw new \LogicException('Temporary produced or consumed more than once');
        }

        // Both value reads and address borrows require a live, initialized source binding.
        $value = $this->body->values[$id - 1] ?? throw new \LogicException('Missing checked value');
        if (in_array($value->kind, [value_kind::local_read, value_kind::local_borrow], true))
        {
            $local_id = $value->payload->local_id;
            if ((!is_int($local_id)) || (!isset($this->live[$local_id])) || (($value->payload->projections === []) && ($value->type_id !== $this->body->local_type_for($local_id)))) {
                throw new \LogicException('Read requires a live initialized local of the checked type');
            }
            if ($this->body->place_type($value->payload) !== $value->type_id) {
                throw new \LogicException('Invalid projected lifetime read');
            }
        }

        // Retain construction order separately from the one-consumer expression access records.
        if (in_array($value->kind, [value_kind::call_result, value_kind::default_construct], true)
            && ($this->body->definition_for($value->type_id)->lifetime->cleanup === \type_model\cleanup_kind::destroy)) {
            $this->temporaries[] = $id;
        }
        $this->live_values[$id] = true;
    }

    /** Record the single consumer of an expression value without ending the underlying object lifetime. */
    private function end_value(int $id, int $statement_id, lifetime_end $end, int $consumer_id = 0): void
    {
        if ((!isset($this->live_values[$id])) || (isset($this->values[$id]))) {
            throw new \LogicException('Temporary consumption requires a live value');
        }
        $value = $this->body->values[$id - 1] ?? throw new \LogicException('Missing checked value');
        $this->contract($value->type_id, $value->source_node_id, in_array($end, [lifetime_end::local_copy, lifetime_end::return_copy, lifetime_end::argument_copy], true));
        if (($end === lifetime_end::assignment_source)
            && ($this->body->definition_for($value->type_id)->lifetime->assignment === \type_model\assignment_kind::unavailable)) {
            $this->fail($value->source_node_id, 'Copy assignment is unavailable for this type');
        }
        unset($this->live_values[$id]);
        $this->values[$id] = new value_lifetime($id, $statement_id, $end, $consumer_id);
    }
}
