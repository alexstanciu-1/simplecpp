<?php
declare(strict_types=1);

/*
 * Role: Bind expression and type syntax with explicit iterative work.
 * Used by: Resolution_Worker (private methods on this owner)
 * Call map: expression() -> expression_node(); application_arguments()
 *          call_expression() -> Function_Lookup::find()
 */
namespace resolve_symbols;

use parse\Syntax_Access;
use parse\syntax_kind;
use collect_symbols\symbol_kind;

/** Scope/initializer guards follow all descendants; no expression is executed or instantiated here. */
trait Expression_Resolution
{
    /** Traverse nodes in source order; type/value roles accompany scheduled template arguments. */
    private function expression(int $id, int $scope_id, int $initializing_local_id = 0,
        ?name_role $role = null, string $description = 'annotation'): void
    {
        $pending = $id === 0 ? [] : [new binding_cursor($id, $role, false)];
        $tree = $this->owner->frontend->syntax;
        while ($pending !== [])
        {
            $cursor = array_pop($pending);
            $node = $tree->nodes[$cursor->node_id - 1];
            if (($cursor->siblings) && ($node->next_sibling_id !== 0)) {
                $pending[] = new binding_cursor($node->next_sibling_id, $cursor->role, true);
            }
            $this->expression_node($cursor, $scope_id, $initializing_local_id, $cursor->description ?? $description, $pending);
        }
    }

    /** Bind one occurrence and schedule its operands; names with no declaration are errors. */
    private function expression_node(binding_cursor $cursor, int $scope, int $initializing,
        string $description, array &$pending): void
    {
        $id = $cursor->node_id;
        $tree = $this->owner->frontend->syntax;
        $node = $tree->nodes[$id - 1];
        if ($node->kind === syntax_kind::template_application)
        {
            if ($cursor->role !== name_role::type) {
                $this->fail($id, 'A template type application is not a value expression');
            }
            $parts = Syntax_Access::template_application_parts($tree, $id);
            $binding = $this->bind_name($parts->name_id, name_role::type_family, $scope, $description);
            $this->application_arguments($id, $binding->target, $pending);
            return;
        }
        if ($node->kind === syntax_kind::name) {
            $this->bind_name($id, $cursor->role ?? name_role::value, $scope, $description);
            return;
        }
        if ($cursor->role === name_role::type) {
            $this->fail($id, 'Expected type argument, not a value expression');
        }
        if (in_array($node->kind, [syntax_kind::field_expression, syntax_kind::index_expression], true))
        {
            $root = Syntax_Access::place_root($tree, $id);
            if ($root === 0) {
                $this->fail($id, 'Field access requires a local storage root');
            }
            // The receiver is bound now; member validity belongs to concrete type checking.
            $this->bind_local($root, $scope, local_access::read, $initializing);
            $indices = $this->place_indices($id);
            foreach (array_reverse($indices) as $index) {
                $pending[] = new binding_cursor($index, null, false);
            }
        }
        elseif ($node->kind === syntax_kind::variable_name) {
            $this->bind_local($id, $scope, local_access::read, $initializing);
        }
        elseif ($node->kind === syntax_kind::call_expression) {
            $this->call_expression($id, $scope, $pending);
        }
        elseif (\parse\Binary_Syntax::operation($node->kind) !== null) {
            $pending[] = new binding_cursor($node->first_child_id, null, true);
        }
        elseif ($node->kind === syntax_kind::construct_expression) {
            $pending[] = new binding_cursor($node->first_child_id, name_role::type, false, 'construction');
        }
        elseif (!in_array($node->kind, [syntax_kind::integer_literal, syntax_kind::string_literal, syntax_kind::boolean_literal], true)) {
            $this->fail($id, 'Unsupported expression for name resolution: ' . $node->kind->name);
        }
    }

    /** Bind the callable definition, then schedule template arguments before runtime arguments. */
    private function call_expression(int $id, int $scope, array &$pending): void
    {
        $tree = $this->owner->frontend->syntax;
        $target_id = Syntax_Access::call_target($tree, $id);
        if ($tree->nodes[$target_id - 1]->kind === syntax_kind::field_expression)
        {
            $receiver = $tree->nodes[$target_id - 1]->first_child_id;
            if ($tree->nodes[$receiver - 1]->kind !== syntax_kind::variable_name) {
                $this->fail($receiver, 'Method calls currently require a local receiver');
            }
            $this->members[] = new member_call_binding($target_id, $receiver);
            $argument = Syntax_Access::first_argument($tree, $id);
            if ($argument !== 0) {
                $pending[] = new binding_cursor($argument, null, true);
            }
            $pending[] = new binding_cursor($receiver, null, false);
            return;
        }
        $application = $tree->nodes[$target_id - 1]->kind === syntax_kind::template_application;
        $name_id = $application ? Syntax_Access::template_application_parts($tree, $target_id)->name_id : $target_id;
        if (isset($this->parameter_names[$this->text($name_id)])) {
            $this->fail($name_id, 'Calling a template parameter is unsupported');
        }
        if (($this->find_constant($this->text($name_id), $scope) !== 0)
            || ($this->symbols->find_symbol($this->text($name_id), $this->owner->namespace_name, symbol_kind::constant_symbol) !== 0)) {
            $this->fail($name_id, 'Calling a constant is not implemented');
        }
        $target = Function_Lookup::find($this->owner, $name_id, $this->symbols);
        if ($target === 0) {
            $this->fail($name_id, "Unknown function '" . $this->text($name_id) . "'");
        }
        $this->calls[] = new symbol_binding($name_id, $target);
        $argument = Syntax_Access::first_argument($tree, $id);
        if ($argument !== 0) {
            $pending[] = new binding_cursor($argument, null, true);
        }
        if ($application) {
            if ($this->symbols->symbol_by_id($target)->kind !== symbol_kind::template_function) {
                $this->fail($target_id, 'Template arguments require a template function');
            }
            $this->application_arguments($target_id, $target, $pending);
        }
        elseif ($this->symbols->symbol_by_id($target)->is_template()) {
            $this->fail($name_id, 'Template argument deduction is not implemented; provide explicit arguments');
        }
    }

    /** Match explicit ordered argument roles to the definition, without evaluating values or forming an instance. */
    private function application_arguments(int $id, int $target, array &$pending): void
    {
        $tree = $this->owner->frontend->syntax;
        $argument = Syntax_Access::template_application_parts($tree, $id)->first_argument_id;
        $definition = $this->symbols->symbol_by_id($target);
        $this->applications[] = new template_application_binding($id, $definition);
        if ($definition->external !== null)
        {
            $arity = $definition->external instanceof \type_model\family_declaration
                ? count($definition->external->definition->parameters) : 1;
            $arguments = [];
            for ($slot = 0; $slot < $arity; ++$slot)
            {
                if ($argument === 0) {
                    $this->fail($id, 'Provider family type argument count mismatch');
                }
                $arguments[] = new binding_cursor($argument, name_role::type, false);
                $argument = $tree->nodes[$argument - 1]->next_sibling_id;
            }
            if ($argument !== 0) {
                $this->fail($id, 'Provider family type argument count mismatch');
            }
            foreach (array_reverse($arguments) as $argument) {
                $pending[] = $argument;
            }
            return;
        }
        $source = $definition->frontend->syntax;
        $list = Syntax_Access::template_parts($source, $definition->declaration_node_id)->parameters_id;
        $arguments = [];
        for ($parameter = $source->nodes[$list - 1]->first_child_id; $parameter !== 0;
            $parameter = $source->nodes[$parameter - 1]->next_sibling_id)
        {
            if ($argument === 0) {
                $this->fail($id, 'Template argument count mismatch');
            }
            $parts = Syntax_Access::template_parameter_parts($source, $parameter);
            $arguments[] = new binding_cursor($argument, $parts->type_syntax_id === 0 ? name_role::type : null, false);
            $argument = $tree->nodes[$argument - 1]->next_sibling_id;
        }
        if ($argument !== 0) {
            $this->fail($id, 'Template argument count mismatch');
        }
        foreach (array_reverse($arguments) as $argument) {
            $pending[] = $argument;
        }
    }

    /** Return only dynamic location operands, in root-to-leaf order; field names remain type-owned. */
    private function place_indices(int $id): array
    {
        $tree = $this->owner->frontend->syntax;
        $indices = [];
        while (in_array($tree->nodes[$id - 1]->kind, [syntax_kind::field_expression, syntax_kind::index_expression], true))
        {
            $node = $tree->nodes[$id - 1];
            if ($node->kind === syntax_kind::index_expression) {
                $indices[] = $tree->nodes[$node->first_child_id - 1]->next_sibling_id;
            }
            $id = $node->first_child_id;
        }
        return array_reverse($indices);
    }

}
