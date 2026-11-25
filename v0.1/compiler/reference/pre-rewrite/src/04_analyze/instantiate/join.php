<?php
declare(strict_types=1);

/*
 * Role: Accept explicit application arguments and allocate exact instance identities.
 * Used by: Concrete_Preparation::prepare_applications()
 * Call map: Instance_Join::join() -> Instance_Store::allocate(); accept(); bind()
 */
namespace instantiate;

final class Instance_Join implements \compile\Join
{
    /** @param list<application_task> $tasks Fixed selected occurrences in the input snapshot. */
    public function __construct(private readonly Instance_Store $input, private readonly array $tasks,
        private readonly \type_model\Type_Store $types, private readonly \resolve_symbols\Resolution_Set $names,
        private readonly \resolve_types\Definition_View $definitions, private readonly \type_model\Type_Catalog $catalog,
        private readonly ?Instance_Set $previous = null)
    {
    }

    /** Validate complete batch provenance, then adopt contexts into the private preparation registry. */
    public function join(array $results): Instance_Store
    {
        if ($this->input->lineage !== $this->types->lineage) {
            throw new \LogicException('Instance join requires its fixed type lineage');
        }
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $owner = $task->context->definition;
            $bindings = $this->names->for_symbol($owner->symbol_id);
            $this->input->template_checks()->require_definition($task->application->definition, $this->names, $this->catalog);
            if (($bindings?->syntax !== $owner->frontend->syntax)
                || !in_array($task->application, $bindings->applications, true)
                || ($this->names->declaration_for($task->application->definition->symbol_id) !== $task->application->definition)
                || (($task->context->instance_id !== 0)
                    && ($this->input->context_for($task->context->context_id) !== $task->context))) {
                throw new \LogicException('Stale application task');
            }
            $key = $task->context->context_id . ':' . $task->application->use_node_id;
            if (isset($selected[$key])) {
                throw new \LogicException('Duplicate application task');
            }
            $selected[$key] = $task;
        }
        $accepted = [];
        foreach ($results as $result)
        {
            $key = $result->task->context->context_id . ':' . $result->task->application->use_node_id;
            if ((($selected[$key] ?? null) !== $result->task) || isset($accepted[$key])) {
                throw new \LogicException('Unexpected or duplicate application result');
            }
            if (($result->arguments === null) ? !$this->pending_matches($result) : !$this->matches($result)) {
                throw new \LogicException('Stale application argument result');
            }
            $accepted[$key] = $result;
        }
        if (count($accepted) !== count($selected)) {
            throw new \LogicException('Incomplete application preparation');
        }

        foreach ($selected as $key => $task)
        {
            $arguments = $accepted[$key]->arguments;
            if ($arguments === null) {
                continue;
            }
            $id = $this->input->allocate($this->types, $task->application->definition->symbol_id, $arguments);
            $context = new instance_context($task->application->definition, $id, $arguments);
            $old = $this->input->context_for($context->context_id);
            $retained = $this->previous?->contexts[$context->context_id] ?? null;
            if (($old === null) && ($retained?->definition === $context->definition)
                && self::same_arguments($retained->arguments, $arguments)) {
                $old = $retained;
            }
            if (($old !== null) && ($old->definition !== $context->definition)) {
                throw new \LogicException('Conflicting current instance definition');
            }
            $context = $old ?? $context;
            $this->input->accept($context);
            $external = $context->definition->external;
            if (($external instanceof \type_model\storage_family) || ($external instanceof \type_model\storage_function)) {
                $family = $external instanceof \type_model\storage_family ? $external : $external->family;
                $type = \resolve_types\Storage_Definitions::materialize($family, $arguments[0]->type, $this->types);
                if ($external instanceof \type_model\storage_family) {
                    $this->input->accept_type($context, $type);
                }
            }
            $this->input->bind($task->context, $task->application->use_node_id, $context);
        }
        return $this->input;
    }

    /** Accept only actual unresolved type arguments as readiness prerequisites. */
    private function pending_matches(application_result $result): bool
    {
        if (!array_is_list($result->prerequisites) || (count(array_unique($result->prerequisites)) !== count($result->prerequisites))) {
            return false;
        }
        $task = $result->task;
        $tree = $task->context->definition->frontend->syntax;
        $node = \parse\Syntax_Access::template_application_parts($tree, $task->application->use_node_id)->first_argument_id;
        if ($task->application->definition->external instanceof \type_model\family_declaration) {
            $expected = Application_Worker::family_arguments($task, $this->names, $this->definitions, $this->input);
            return ($expected->arguments === null) && ($expected->prerequisites === $result->prerequisites);
        }
        if ($task->application->definition->external !== null) {
            return ($result->prerequisites === [$node])
                && (Bindings::type($task->context, $node, $this->names, $this->definitions, $this->input) === null);
        }
        $missing = [];
        foreach ($this->names->for_symbol($task->application->definition->symbol_id)->template_parameters as $parameter) {
            if (($parameter->type_syntax_id === 0)
                && (Bindings::type($task->context, $node, $this->names, $this->definitions, $this->input) === null)) {
                $missing[] = $node;
            }
            $node = $tree->nodes[$node - 1]->next_sibling_id;
        }
        return array_diff($result->prerequisites, $missing) === [];
    }

    /** Validate argument provenance against the fixed syntax and accepted prerequisites. */
    private function matches(application_result $result): bool
    {
        $task = $result->task;
        if ($task->application->definition->external instanceof \type_model\family_declaration) {
            $expected = Application_Worker::family_arguments($task, $this->names, $this->definitions, $this->input);
            return ($expected->arguments !== null) && array_is_list($result->arguments)
                && self::same_arguments($expected->arguments, $result->arguments);
        }
        if ($task->application->definition->external !== null)
        {
            $node = \parse\Syntax_Access::template_application_parts($task->context->definition->frontend->syntax,
                $task->application->use_node_id)->first_argument_id;
            $type = Bindings::type($task->context, $node, $this->names, $this->definitions, $this->input);
            return ($type !== null) && array_is_list($result->arguments)
                && \resolve_types\Storage_Definitions::eligible($type) && (count($result->arguments) === 1)
                && ($result->arguments[0]->type === $type) && ($result->arguments[0]->value === null);
        }
        $parameters = $this->names->for_symbol($task->application->definition->symbol_id)->template_parameters;
        if (!array_is_list($result->arguments) || (count($parameters) !== count($result->arguments))) {
            return false;
        }
        $tree = $task->context->definition->frontend->syntax;
        $node = \parse\Syntax_Access::template_application_parts($tree, $task->application->use_node_id)->first_argument_id;
        foreach ($parameters as $index => $parameter)
        {
            $argument = $result->arguments[$index];
            if ($parameter->type_syntax_id === 0) {
                $type = Bindings::type($task->context, $node, $this->names, $this->definitions, $this->input);
                if (($type === null) || (\type_model\Generic_Contracts::missing($type, $parameter->contract) !== null)) {
                    return false;
                }
                $expected = new template_argument($type);
            }
            else {
                $expected = Bindings::value($task->context, $node, $this->names, $this->catalog, $this->input);
            }
            if (($argument->type !== $expected->type) || ($argument->value !== $expected->value)) {
                return false;
            }
            $node = $tree->nodes[$node - 1]->next_sibling_id;
        }
        return $node === 0;
    }

    /** Exact semantic equality without recursively comparing entire provider/source snapshots. */
    private static function same_arguments(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $index => $argument) {
            if (($argument->type !== $right[$index]->type) || ($argument->value !== $right[$index]->value)) {
                return false;
            }
        }
        return true;
    }
}
