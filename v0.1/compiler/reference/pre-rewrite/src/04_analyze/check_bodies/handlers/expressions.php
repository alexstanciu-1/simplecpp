<?php
declare(strict_types=1);

/*
 * Role: Check expression values, arguments and operations.
 * Used by: Body_Worker (private trait methods on this owner)
 * Call map:
 *   check_expression()
 *     -> begin_call() / resume_binary() / begin_place() / check_leaf() [by kind]
 *   check_argument() / bound_call() -> argument_value() [value or borrowed access]
 */

namespace check_bodies;

use parse\syntax_kind;
use parse\Syntax_Access;
use type_model\representation_kind;

// Private methods composed only by this process's callable worker.
trait Expression_Checking
{
    /**
     * One expression path, including nested arguments. Values are appended when
     * evaluated; calls are appended after their arguments, including void calls.
     * Per-call argument ranges remain contiguous even when nested calls add rows.
     */
    private function check_expression(int $node_id, int $expected_type = 0): int
    {
        $tree = $this->owner->frontend->syntax;
        $pending = [];
        $value_id = 0;
        while (true)
        {
            if ($node_id !== 0)
            {
                $node = $tree->nodes[$node_id - 1];
                if ($node->kind === syntax_kind::call_expression) {
                    $cursor = $this->begin_call($node_id);
                    $pending[] = $cursor;
                    $node_id = $cursor->next_argument_id;
                    $expected_type = $node_id === 0 ? 0 : $this->types->parameter_type_for($cursor->target_callable_id, $cursor->argument_index + 1);
                    continue;
                }
                if (\parse\Binary_Syntax::operation($node->kind) !== null) {
                    $pending[] = new operation_cursor($node_id, $node->first_child_id);
                    $node_id = $node->first_child_id;
                    $expected_type = 0;
                    continue;
                }
                if (in_array($node->kind, [syntax_kind::variable_name, syntax_kind::field_expression, syntax_kind::index_expression], true))
                {
                    $cursor = $this->begin_place($node_id, false);
                    $node_id = $this->advance_place($cursor);
                    if ($node_id !== 0) {
                        $pending[] = $cursor;
                        $expected_type = 0;
                        continue;
                    }
                    $value_id = $this->finish_place_value($cursor);
                }
                else {
                    $value_id = $this->check_leaf($node_id, $expected_type);
                }
                $node_id = 0;
            }
            if ($pending === []) {
                return $value_id;
            }

            // Resume the suspended place, operation or call after its child value is ready.
            $cursor = $pending[count($pending) - 1];
            if ($cursor instanceof place_cursor)
            {
                $node_id = $this->advance_place($cursor, $value_id);
                $expected_type = 0;
                if ($node_id === 0) {
                    $value_id = $this->finish_place_value($cursor);
                    array_pop($pending);
                }
                continue;
            }
            if ($cursor instanceof operation_cursor) {
                $node_id = $this->resume_binary($cursor, $value_id);
                if ($node_id === 0) {
                    array_pop($pending);
                }
                continue;
            }
            if ($cursor->next_argument_id !== 0) {
                $node_id = $this->check_argument($cursor, $value_id);
                $expected_type = $node_id === 0 ? 0 : $this->types->parameter_type_for($cursor->target_callable_id, $cursor->argument_index + 1);
                continue;
            }

            // Complete the call only after all arguments have been checked in source order.
            $value_id = $this->finish_call($cursor);
            array_pop($pending);
        }
    }

    /** Resolve the callable and reserve contiguous argument rows before checking nested expressions. */
    private function begin_call(int $node_id): expression_cursor
    {
        $tree = $this->owner->frontend->syntax;
        $target_node = Syntax_Access::call_target($tree, $node_id);
        $member = $tree->nodes[$target_node - 1]->kind === syntax_kind::field_expression;
        if (($member) || ($tree->nodes[$target_node - 1]->kind === syntax_kind::template_application)) {
            $context = $this->instance ?? new \instantiate\instance_context($this->owner);
            $target = $this->types->instances->application($context, $target_node)?->context_id
                ?? throw new \LogicException('Missing prepared template call');
        }
        else {
            $target = $this->names->target_for($target_node);
        }
        $signature = $this->signature($target);
        $argument = Syntax_Access::first_argument($tree, $node_id);
        if (($argument !== 0) && ($signature->count === ($member ? 1 : 0))) {
            $this->arity_error($argument);
        }

        // Reserve this call's argument range before nested calls append their own rows.
        $start = count($this->arguments);
        for ($i = 0; $i < $signature->count; ++$i) {
            $this->arguments[] = null;
        }
        // Keep semantic parameter positions intact; explicit arguments skip the receiver slot.
        $receiver_index = !$member ? -1 : ($this->types->for_callable($target)->receiver_index
            ?? throw new \LogicException('Method signature lost its receiver position'));
        $cursor = new expression_cursor($node_id, $target, $signature->return_type,
            $start, $signature->count, $argument, $receiver_index);
        if ($member)
        {
            $receiver_node = $tree->nodes[$target_node - 1]->first_child_id;
            $receiver = $this->check_place_value($receiver_node);
            $type = $this->types->parameter_type_for($target, $receiver_index + 1);
            $passing = $signature->parameter_passing[$receiver_index];
            $receiver = $this->argument_value($receiver, $type, $passing, $receiver_node);
            $this->arguments[$start + $receiver_index] = new typed_argument($receiver, $type, $passing);
            $cursor->argument_index = $receiver_index === 0 ? 1 : 0;
        }
        return $cursor;
    }

    /** Dispatch supported leaf expressions and diagnose unsupported syntax at its source location. */
    private function check_leaf(int $node_id, int $expected_type): int
    {
        $kind = $this->owner->frontend->syntax->nodes[$node_id - 1]->kind;
        return match ($kind)
        {
            syntax_kind::construct_expression => $this->check_construction($node_id),
            syntax_kind::integer_literal => $this->check_integer_literal($node_id),
            syntax_kind::boolean_literal => $this->check_boolean_literal($node_id),
            syntax_kind::name => $this->check_constant($node_id),
            syntax_kind::string_literal => $this->check_byte_literal($node_id, $expected_type),
            default => $this->fail($node_id, 'Unsupported expression for body checking'),
        };
    }

    /** Resolve a construction type in the fixed definition view, then use the same default-value path as typed locals. */
    private function check_construction(int $node_id): int
    {
        $name = $this->owner->frontend->syntax->nodes[$node_id - 1]->first_child_id;
        $type = $this->types->construction_type($this->owner, $name, $this->instance);
        $this->retain_type($type);
        return $this->default_value($node_id, $type);
    }

    /** Default-initialize inline values from explicit type capabilities; scalar defaults retain their existing source rules. */
    private function default_value(int $node_id, int $type): int
    {
        if (!in_array($this->types->definition_for($type)->representation->kind, [representation_kind::structure, representation_kind::opaque_inline], true)) {
            $this->fail($node_id, 'Default construction requires a supported inline type');
        }
        $construction = $this->types->definition_for($type)->lifetime->construction;
        if ($construction === \type_model\construction_kind::unavailable) {
            $this->fail($node_id, 'Default construction is unavailable for a constituent field');
        }
        $kind = $construction === \type_model\construction_kind::zero ? value_kind::record_default : value_kind::default_construct;
        return $this->append_value(new typed_value($node_id, $type, $kind, null));
    }

    /** Boolean spelling uses the configured boolean role, independently of its source type name. */
    private function check_boolean_literal(int $node_id): int
    {
        $type = $this->types->boolean_type();
        if ($type === 0) {
            $this->fail($node_id, 'No boolean type contract is configured');
        }
        $node = $this->owner->frontend->syntax->nodes[$node_id - 1];
        $text = substr($this->owner->frontend->tokens->source->content, $node->start, $node->length);
        return $this->append_value(new typed_value($node_id, $type, value_kind::integer_literal, $text === 'true' ? '1' : '0'));
    }

    /** Resolve literal text under the catalog integer contract and report range errors at the source node. */
    private function check_integer_literal(int $node_id): int
    {
        $node = $this->owner->frontend->syntax->nodes[$node_id - 1];
        $definition = $this->types->types->definition_for_type($this->integer_literal_type);
        $text = substr($this->owner->frontend->tokens->source->content, $node->start, $node->length);
        try {
            $literal = Integer_Literals::resolve($text, $definition);
        }
        catch (\RangeException $error) {
            $this->fail($node_id, $error->getMessage());
        }
        return $this->append_value(new typed_value($node_id, $this->integer_literal_type, value_kind::integer_literal, $literal));
    }

    /** Read a prepared literal constant or integer parameter through the fixed instance environment. */
    private function check_constant(int $node_id): int
    {
        $argument = \instantiate\Bindings::value($this->instance ?? new \instantiate\instance_context($this->owner),
            $node_id, $this->types->names, $this->types->catalog, $this->types->instances ?? new \instantiate\Instance_Set());
        $type = $this->types->types->find_type($argument->type->name, $argument->type->namespace_name);
        return $this->append_value(new typed_value($node_id, $type, value_kind::integer_literal, $argument->value));
    }

    /** Decode source bytes and select a metadata-bound constructor, or supply a requested raw span argument. */
    private function check_byte_literal(int $node_id, int $expected_type): int
    {
        $node = $this->owner->frontend->syntax->nodes[$node_id - 1];
        $text = substr($this->owner->frontend->tokens->source->content, $node->start, $node->length);
        try {
            $bytes = Byte_Literals::decode($text);
        }
        catch (\InvalidArgumentException $error) {
            $this->fail($node_id, $error->getMessage());
        }
        if (($expected_type !== 0) && ($this->types->definition_for($expected_type)->representation->kind === representation_kind::byte_span)) {
            return $this->append_value(new typed_value($node_id, $expected_type, value_kind::byte_literal, new byte_literal($bytes)));
        }

        // A contextual binding is explicit provider permission; untyped literals use the declared default.
        $target = $this->types->language_callable(\type_model\language_binding::byte_literal, $expected_type);
        if ($target === 0) {
            $this->fail($node_id, 'No byte-literal construction contract for this context');
        }
        $signature = $this->signature($target);
        $parameter = $this->types->parameter_type_for($target, 1);
        $value = $this->append_value(new typed_value($node_id, $parameter, value_kind::byte_literal, new byte_literal($bytes)));
        return $this->bound_call($node_id, $target, $signature, $value);
    }

    /** Build a one-argument language-bound operation as an ordinary checked call with ordinary dependencies. */
    private function bound_call(int $node_id, int $target, \type_model\signature_representation $signature, int $value): int
    {
        $passing = $signature->parameter_passing[0];
        $type = $this->types->parameter_type_for($target, 1);
        $value = $this->argument_value($value, $type, $passing, $node_id);
        $start = count($this->arguments);
        $this->arguments[] = new typed_argument($value, $type, $passing);
        $cursor = new expression_cursor($node_id, $target, $signature->return_type, $start, 1, 0);
        $cursor->argument_index = 1;
        return $this->finish_call($cursor);
    }

    /**
     * Return the next operand node, or zero after replacing value_id with the completed result.
     */
    private function resume_binary(operation_cursor $cursor, int &$value_id): int
    {
        $tree = $this->owner->frontend->syntax;
        if ($value_id === 0) {
            $this->fail($cursor->source_node_id, 'Binary operation requires value operands');
        }
        $cursor->operands[] = $value_id;
        $node_id = $tree->nodes[$cursor->next_operand_id - 1]->next_sibling_id;
        $cursor->next_operand_id = $node_id;
        if ($node_id !== 0) {
            return $node_id;
        }
        if (count($cursor->operands) !== 2) {
            throw new \LogicException('Binary operation requires two parsed operands');
        }
        [$left, $right] = $cursor->operands;
        $left_type = $this->values[$left - 1]->type_id;
        $right_type = $this->values[$right - 1]->type_id;
        $operator = \parse\Binary_Syntax::operation($tree->nodes[$cursor->source_node_id - 1]->kind);
        $contract = $this->operation_contracts[$operator][$left_type][$right_type]
            ??= Operation_Resolver::binary($this->types->types, $operator, $left_type, $right_type, $this->types->boolean_type());
        if ($contract === null) {
            $this->fail($cursor->source_node_id, 'Unsupported ' . $operator . ' operand types or result contract');
        }
        $this->select_place_access($left, false);
        $this->select_place_access($right, false);
        $value_id = $this->append_value(new typed_value($cursor->source_node_id, $contract->result_type,
            value_kind::operation, new operation_value($left, $right, $contract)));
        return 0;
    }

    /**
     * Fill this call's reserved argument slot, then return the next sibling node.
     */
    private function check_argument(expression_cursor $cursor, int $value_id): int
    {
        $tree = $this->owner->frontend->syntax;
        $destination = $this->types->parameter_type_for($cursor->target_callable_id, $cursor->argument_index + 1);
        $passing = $this->types->signature_for($cursor->target_callable_id)->parameter_passing[$cursor->argument_index];
        $value_id = $this->argument_value($value_id, $destination, $passing, $cursor->next_argument_id);
        $this->arguments[$cursor->argument_start + $cursor->argument_index] = new typed_argument($value_id, $destination, $passing);
        ++$cursor->argument_index;
        if ($cursor->argument_index === $cursor->receiver_index) {
            ++$cursor->argument_index;
        }
        $node_id = $tree->nodes[$cursor->next_argument_id - 1]->next_sibling_id;
        $cursor->next_argument_id = $node_id;
        if (($node_id !== 0) && ($cursor->argument_index === $cursor->argument_count)) {
            $this->arity_error($node_id);
        }
        return $node_id;
    }

    /** Select value access or existing storage at the argument boundary, before lifetime analysis or loads exist. */
    private function argument_value(int $id, int $destination, \type_model\argument_passing $passing, int $node_id): int
    {
        $id = $this->convert($id, $destination, conversion_use::argument, $node_id);
        $shape = $this->types->definition_for($destination)->representation->kind;
        $object = in_array($shape, [representation_kind::opaque_inline, representation_kind::structure], true);
        if (($object) && !$passing->is_borrow()) {
            $this->fail($node_id, 'Unsupported inline object value argument; a call-scoped borrow is required');
        }

        // Record and mutable borrows require an existing local address; immutable opaque temporaries remain borrowable.
        if ($passing->is_borrow() && (($shape === representation_kind::structure)
            || ($passing === \type_model\argument_passing::borrow_mutable)))
        {
            $value = $this->values[$id - 1];
            if (!$value instanceof pending_place_value) {
                $this->fail($node_id, 'Record or mutable borrowing requires existing local storage; temporary record borrowing is unsupported');
            }
            if (($passing === \type_model\argument_passing::borrow_mutable) && !$this->place_writable($value->location)) {
                $this->fail($node_id, 'A const reference cannot be passed as a mutable reference');
            }
        }
        $this->select_place_access($id, $passing->is_borrow());
        return $id;
    }

    /** Complete the checked call after arity validation and produce a value only for non-void results. */
    private function finish_call(expression_cursor $cursor): int
    {
        if ($cursor->argument_index !== $cursor->argument_count) {
            $this->arity_error($cursor->source_node_id);
        }
        $target = $cursor->target_callable_id;
        $purpose = $this->types->for_callable($target)->external?->conversion_purpose;
        if ($purpose !== null)
        {
            // The source entry supplies a declared argument boundary, then requests its exact conversion purpose.
            $argument = $this->arguments[$cursor->argument_start];
            $source = $this->values[$argument->value_id - 1]->type_id;
            $selection = Conversion_Resolver::resolve($this->types, new conversion_request($source, $cursor->return_type_id, $purpose));
            if (($selection?->form !== conversion_form::provider_call) || ($selection->callable_id !== $target)) {
                $this->fail($cursor->source_node_id, 'Named conversion does not select its declared operation');
            }
            $target = $selection->callable_id;
        }
        $has_result = $this->types->types->representation_for_type($cursor->return_type_id)->kind !== representation_kind::void_type;
        $this->calls[] = new typed_call($cursor->source_node_id, $target,
            $has_result ? count($this->values) + 1 : 0, $cursor->argument_start, $cursor->argument_count);
        return $has_result ? $this->append_value(new typed_value($cursor->source_node_id,
                $cursor->return_type_id, value_kind::call_result, count($this->calls))) : 0;
    }

    private function arity_error(int $node_id): never
    {
        $this->fail($node_id, 'Call argument count does not match the resolved signature');
    }

}
