<?php
declare(strict_types=1);

/*
 * Role: Check generic permissions once per source definition, before substitution.
 * Used by: Template_Checker
 * Call map: Template_Worker::check() -> fields(); locals(); statements()
 *   expression() -> call(); Terms::annotation(); field(); method()
 *   call() -> provider_call() [declared provider member]; otherwise source signature
 */
namespace check_templates;

use parse\Syntax_Access;
use parse\syntax_kind;

final class Template_Worker
{
    private Terms $terms;
    /** @var array<int, expression_type> Symbolic local types under this definition’s binding IDs. */
    private array $locals = [];
    private int $visited = 0;

    public function __construct(private readonly definition_task $task,
        \collect_symbols\Symbol_Store $symbols, \resolve_symbols\Resolution_Set $names,
        \type_model\Type_Catalog $catalog)
    {
        $this->terms = new Terms($symbols, $names, $catalog);
    }

    /** Validate dependent uses without selecting concrete instances or preparing executable IR. */
    public function check(): definition_result
    {
        $owner = $this->task->owner;
        ++$this->visited;
        // Keep only accepted provenance; symbolic locals and expression terms are worker scratch.
        $this->terms->declaration($owner->symbol_id);
        $this->terms->bindings($owner);
        if ($owner->kind === \collect_symbols\symbol_kind::template_struct) {
            $this->fields();
        }
        if ($owner->body_node_id !== 0) {
            $this->locals();
            $this->statements();
        }
        return new definition_result($this->task, $this->terms->catalog, $this->terms->dependencies,
            $this->terms->binding_dependencies, $this->visited);
    }

    /** Field declaration does not construct an instance, but dependent fixed-array initialization is parked. */
    private function fields(): void
    {
        $owner = $this->task->owner;
        $defaults_members = false;
        foreach ($this->terms->symbols->child_symbol_ids($owner->symbol_id) as $member_id) {
            $member = $this->terms->declaration($member_id);
            $role = \resolve_types\Source_Lifecycle::role($member);
            $defaults_members = ($defaults_members)
                || ($role?->composition(true)->member_kind === \type_model\lifecycle_operation_kind::default_construct);
        }
        $member_cursor = Syntax_Access::struct_members($owner->frontend->syntax, $owner->declaration_node_id, syntax_kind::field_declaration);
        while ($member_cursor->advance())
        {
            $field = $member_cursor->current();
            ++$this->visited;
            $parts = Syntax_Access::field_declaration_parts($owner->frontend->syntax, $field);
            $type = $this->terms->annotation($owner, $parts->type_syntax_id);
            if (($defaults_members) && ($type->dependent)) {
                Terms::fail($owner, $field, 'Generic contract does not permit implicit field default construction in custom lifecycle');
            }
            if (($parts->extent_id !== 0) && ($type->dependent)) {
                Terms::fail($owner, $field, 'Generic fixed-array initialization is deferred; the default contract does not guarantee initialization');
            }
        }
    }

    /** Bind local IDs to symbolic annotations; actual value lifetime analysis remains downstream. */
    private function locals(): void
    {
        $owner = $this->task->owner;
        foreach ($this->task->bindings->locals as $index => $local)
        {
            if ($local->receiver) {
                $this->locals[$index + 1] = new expression_type($this->terms->receiver($owner), $owner->receiver_const);
                continue;
            }
            $tree = $owner->frontend->syntax;
            $parameter = $tree->nodes[$local->declaration_node_id - 1]->kind === syntax_kind::parameter_declaration;
            $parts = $parameter ? Syntax_Access::parameter_parts($tree, $local->declaration_node_id)
                : Syntax_Access::local_declaration_parts($tree, $local->declaration_node_id);
            $type = $this->terms->annotation($owner, $parts->type_syntax_id);
            $readonly = ($parameter) && ($parts->reference === syntax_kind::const_reference_annotation);
            if (($parameter) && ($parts->reference === null)) {
                $this->terms->provider_value_use($type, $owner, $local->declaration_node_id);
            }
            if (($parameter) && ($type->kind === term_kind::parameter)
                && ($parts->reference === syntax_kind::reference_annotation)) {
                Terms::fail($owner, $local->declaration_node_id, 'Mutable borrowing of bare generic T is not in the initial contract');
            }
            $this->locals[$index + 1] = new expression_type($type, $readonly);
        }
    }

    /** Visit both ordinary branches in source order; no call-graph or runtime-path expansion. */
    private function statements(): void
    {
        $owner = $this->task->owner;
        $tree = $owner->frontend->syntax;
        $parts = Syntax_Access::function_parts($tree, Syntax_Access::underlying_declaration($tree, $owner->declaration_node_id));
        $return = $this->terms->annotation($owner, $parts->return_type_id);
        // Both runtime branches must respect the same permissions; never enumerate execution paths.
        $pending = [$owner->body_node_id];
        while ($pending !== [])
        {
            $id = array_pop($pending);
            $node = $tree->nodes[$id - 1];
            ++$this->visited;
            switch ($node->kind)
            {
                case syntax_kind::block:
                    foreach (array_reverse($this->terms->arguments($owner, $node->first_child_id)) as $child) {
                        $pending[] = $child;
                    }
                    break;
                case syntax_kind::if_statement:
                case syntax_kind::while_statement:
                    $control = Syntax_Access::control_parts($tree, $id);
                    $this->non_generic($this->expression($control->condition), $control->condition, 'condition conversion');
                    if ($control->alternative !== 0) {
                        $pending[] = $control->alternative;
                    }
                    $pending[] = $control->body;
                    break;
                case syntax_kind::constexpr_if_statement:
                case syntax_kind::consteval_if_statement:
                    Terms::fail($owner, $id, 'Unsupported compile-time branch; constant evaluation is not implemented');
                case syntax_kind::local_declaration:
                    $local = Syntax_Access::local_declaration_parts($tree, $id);
                    $target = $this->locals[$this->task->bindings->local_for_declaration($id)];
                    if ($local->initializer_id === 0) {
                        $this->terms->default_construction($target->type, $owner, $id);
                    }
                    else {
                        $this->terms->provider_value_use($target->type, $owner, $id);
                        $this->compatible($target->type, $this->expression($local->initializer_id)->type, $id);
                    }
                    break;
                case syntax_kind::assignment_statement:
                    $write = Syntax_Access::assignment_parts($tree, $id);
                    $target = $this->expression($write->target_id);
                    if ($target->readonly) {
                        Terms::fail($owner, $id, 'Cannot assign through a const reference in a generic definition');
                    }
                    $this->terms->provider_value_use($target->type, $owner, $id);
                    $this->compatible($target->type, $this->expression($write->value_id)->type, $id);
                    break;
                case syntax_kind::return_statement:
                    if ($node->first_child_id !== 0) {
                        $this->terms->provider_value_use($return, $owner, $id);
                        $this->compatible($return, $this->expression($node->first_child_id)->type, $id);
                    }
                    break;
                case syntax_kind::expression_statement:
                    $this->expression($node->first_child_id);
                    break;
                case syntax_kind::echo_statement:
                    foreach ($this->terms->arguments($owner, $node->first_child_id) as $value) {
                        $this->non_generic($this->expression($value), $value, 'output conversion');
                    }
                    break;
                default:
                    Terms::fail($owner, $id, 'Unsupported statement in generic definition: ' . $node->kind->name);
            }
        }
    }

    /** Evaluate only symbolic expression types, using a private iterative postorder walk. */
    private function expression(int $root): expression_type
    {
        $owner = $this->task->owner;
        $tree = $owner->frontend->syntax;
        $pending = [[$root, false]];
        $values = [];
        while ($pending !== [])
        {
            [$id, $finish] = array_pop($pending);
            $node = $tree->nodes[$id - 1];
            $children = [];
            if ($node->kind === syntax_kind::call_expression)
            {
                $children = $this->terms->arguments($owner, Syntax_Access::first_argument($tree, $id));
                $target = Syntax_Access::call_target($tree, $id);
                if ($tree->nodes[$target - 1]->kind === syntax_kind::field_expression) {
                    array_unshift($children, $tree->nodes[$target - 1]->first_child_id);
                }
            }
            elseif ($node->kind === syntax_kind::field_expression) {
                $children = [$node->first_child_id];
            }
            elseif (($node->kind === syntax_kind::index_expression) || (\parse\Binary_Syntax::operation($node->kind) !== null)) {
                $children = $this->terms->arguments($owner, $node->first_child_id);
            }
            // Resume the parent after its immediate operands have symbolic results.
            if ((!$finish) && ($children !== []))
            {
                $pending[] = [$id, true];
                foreach (array_reverse($children) as $child) {
                    $pending[] = [$child, false];
                }
                continue;
            }
            ++$this->visited;
            $value = new expression_type(null);
            switch ($node->kind)
            {
                case syntax_kind::variable_name:
                    $value = $this->locals[$this->task->bindings->binding_for($id)->local_id];
                    break;
                case syntax_kind::integer_literal:
                case syntax_kind::name:
                    $value = new expression_type(new type_term(term_kind::named, $this->terms->catalog->integer_literal_type));
                    break;
                case syntax_kind::boolean_literal:
                case syntax_kind::string_literal:
                    break;
                case syntax_kind::construct_expression:
                    $value = new expression_type($this->terms->annotation($owner, $node->first_child_id));
                    $this->terms->default_construction($value->type, $owner, $id);
                    break;
                case syntax_kind::field_expression:
                    $receiver = $values[$children[0]];
                    if ($receiver->type !== null) {
                        $name = $tree->nodes[$children[0] - 1]->next_sibling_id;
                        $value = new expression_type($this->terms->field($receiver->type, ltrim(Terms::text($owner, $name), '$'), $owner, $id), $receiver->readonly);
                    }
                    break;
                case syntax_kind::index_expression:
                    $receiver = $values[$children[0]];
                    $this->non_generic($values[$children[1]], $children[1], 'index conversion');
                    if ($receiver->type?->kind === term_kind::array_type) {
                        $value = new expression_type($receiver->type->arguments[0], $receiver->readonly);
                    }
                    else {
                        $this->non_generic($receiver, $id, 'index access');
                    }
                    break;
                case syntax_kind::call_expression:
                    $value = $this->call($id, array_map(static fn($child) => $values[$child], $children));
                    break;
                default:
                    if (\parse\Binary_Syntax::operation($node->kind) === null) {
                        Terms::fail($owner, $id, 'Unsupported expression in generic definition: ' . $node->kind->name);
                    }
                    foreach ($children as $child) {
                        $this->non_generic($values[$child], $child, 'operator ' . $node->kind->name);
                    }
                    break;
            }
            $values[$id] = $value;
        }
        return $values[$root];
    }

    /** Match declared parameter/result terms; never inspect a callee body or concrete specialization.
     * @param list<expression_type> $values */
    private function call(int $id, array $values): expression_type
    {
        $owner = $this->task->owner;
        $tree = $owner->frontend->syntax;
        $target = Syntax_Access::call_target($tree, $id);
        $node = $tree->nodes[$target - 1];
        $arguments = [];
        if ($node->kind === syntax_kind::field_expression)
        {
            $receiver = array_shift($values);
            if ($receiver->type === null) {
                Terms::fail($owner, $id, 'Unsupported symbolic method receiver');
            }
            $name = $tree->nodes[$node->first_child_id - 1]->next_sibling_id;
            $callee = $this->terms->method($receiver->type, Terms::text($owner, $name), $owner, $id);
            if (($receiver->readonly) && (!$callee->receiver_const)) {
                Terms::fail($owner, $id, 'Mutable method requires a non-const receiver');
            }
            $arguments = $receiver->type->arguments;
        }
        else
        {
            $name = $target;
            if ($node->kind === syntax_kind::template_application)
            {
                $application = Syntax_Access::template_application_parts($tree, $target);
                $name = $application->name_id;
                foreach ($this->terms->arguments($owner, $application->first_argument_id) as $argument) {
                    $term = $this->terms->annotation($owner, $argument);
                    $this->terms->forwarded_type($term, $owner, $argument);
                    $arguments[] = $term;
                }
            }
            $callee = $this->terms->declaration($this->task->bindings->target_for($name));
        }
        if ($callee->external instanceof \type_model\family_method) {
            return $this->provider_call($callee->external, $receiver, $values, $id);
        }
        if ($callee->external !== null)
        {
            foreach ($arguments as $argument) {
                $this->non_generic(new expression_type($argument), $id, 'provider storage family requirements');
            }
            foreach ($values as $value) {
                $this->non_generic($value, $id, 'forwarding to a concrete provider parameter');
            }
            return new expression_type(null);
        }
        // Read the declared signature only. Each callee body has its own selected permission task.
        $this->terms->bindings($callee);
        $parts = Syntax_Access::function_parts($callee->frontend->syntax,
            Syntax_Access::underlying_declaration($callee->frontend->syntax, $callee->declaration_node_id));
        $parameter_ids = $this->terms->arguments($callee, $callee->frontend->syntax->nodes[$parts->parameters_id - 1]->first_child_id);
        if (count($parameter_ids) !== count($values)) {
            Terms::fail($owner, $id, 'Generic call argument count mismatch');
        }
        foreach ($parameter_ids as $index => $parameter)
        {
            $part = Syntax_Access::parameter_parts($callee->frontend->syntax, $parameter);
            $type = $this->terms->annotation($callee, $part->type_syntax_id, $arguments);
            if ($part->reference === null) {
                $this->terms->provider_value_use($type, $owner, $id);
            }
            $this->compatible($type, $values[$index]->type, $id);
            if (($part->reference === syntax_kind::reference_annotation) && ($values[$index]->readonly)) {
                Terms::fail($owner, $id, 'Cannot forward const reference to mutable parameter');
            }
        }
        return new expression_type($this->terms->annotation($callee, $parts->return_type_id, $arguments));
    }

    /** Check exposed members through their semantic signature, omitting only the declared receiver position.
     * Native bindings, ABI preparation and callee bodies cannot add permissions here. */
    private function provider_call(\type_model\family_method $method, expression_type $receiver, array $values, int $node): expression_type
    {
        $operation = $method->operation;
        $signature = $operation->signature;
        if (count($values) !== (count($signature->parameters) - 1)) {
            Terms::fail($this->task->owner, $node, 'Generic call argument count mismatch');
        }
        $actual = 0;
        foreach ($signature->parameters as $index => $parameter)
        {
            $value = $index === $operation->receiver ? $receiver : $values[$actual++];
            $expected = $this->terms->provider_type($parameter->type, $method->family, $receiver->type);
            if ($parameter->passing === \type_model\argument_passing::value) {
                $this->terms->provider_value_use($expected, $this->task->owner, $node);
            }
            $this->compatible($expected, $value->type, $node);
            if (($parameter->passing === \type_model\argument_passing::borrow_mutable)
                && ($value->type?->kind === term_kind::parameter)) {
                Terms::fail($this->task->owner, $node, 'Mutable borrowing of bare generic T is not in the initial contract');
            }
            if (($parameter->passing === \type_model\argument_passing::borrow_mutable) && ($value->readonly)) {
                Terms::fail($this->task->owner, $node, 'Cannot forward const reference to mutable parameter');
            }
        }
        // Family_Contracts already proves every operation requirement is covered by its formal baseline.
        foreach ($operation->requirements as $requirement) {
            $this->terms->family_argument($receiver->type->arguments[$requirement->slot], $this->task->owner, $node);
        }
        return new expression_type($this->terms->provider_type($signature->result->type, $method->family, $receiver->type));
    }

    /** Only dependent identities are checked here; ordinary conversions remain concrete body work. */
    private function compatible(?type_term $target, ?type_term $source, int $node): void
    {
        if ((($target?->dependent ?? false) || ($source?->dependent ?? false))
            && (($target === null) || ($source === null) || !Terms::same($target, $source))) {
            Terms::fail($this->task->owner, $node, 'Generic contract requires the same declared type; concrete substitution cannot authorize this conversion');
        }
    }

    private function non_generic(expression_type $value, int $node, string $operation): void
    {
        if ($value->type?->dependent ?? false) {
            Terms::fail($this->task->owner, $node, 'Generic contract does not permit ' . $operation);
        }
    }
}
