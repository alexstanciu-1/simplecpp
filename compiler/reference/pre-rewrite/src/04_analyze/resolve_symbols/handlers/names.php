<?php
declare(strict_types=1);

/*
 * Role: Bind declaration annotations, template parameters and constant references.
 * Used by: Resolution_Worker (private methods on this owner)
 * Call map: definition() -> template_scope(); function/field annotations
 *           bind_name() -> parameter/constant scopes; Declaration_Lookup::find()
 */
namespace resolve_symbols;

use parse\Syntax_Access;
use parse\syntax_kind;

/** Name binding without concrete type preparation; all maps and output rows belong to one worker. */
trait Name_Resolution
{
    /** Establish parameter bindings, then visit the ordinary wrapped declaration's annotations. */
    private function definition(): void
    {
        if ($this->owner->declaration_node_id === 0) {
            return;
        }
        $tree = $this->owner->frontend->syntax;
        $id = $this->owner->declaration_node_id;
        if ($this->owner->is_template()) {
            $this->template_scope($this->owner->template_parameters_node_id);
            $id = Syntax_Access::underlying_declaration($tree, $id);
        }
        $id = Syntax_Access::underlying_declaration($tree, $id);
        $kind = $tree->nodes[$id - 1]->kind;
        if (($kind === syntax_kind::struct_declaration)
            && (($this->catalog->find_type($this->owner->name, $this->owner->namespace_name) !== null)
                || ($this->catalog->find_record($this->owner->name, $this->owner->namespace_name) !== null))) {
            $this->fail(Syntax_Access::struct_parts($tree, $id)->name_id,
                'Duplicate source/provider type: ' . $this->owner->name);
        }
        if ($kind === syntax_kind::function_declaration) {
            $parts = Syntax_Access::function_parts($tree, $id);
            $this->annotation($parts->return_type_id, 0, 'return');
        }
        elseif ($kind === syntax_kind::struct_declaration)
        {
            $parts = Syntax_Access::struct_parts($tree, $id);
            $fields = [];
            $member_cursor = Syntax_Access::struct_members($tree, $this->owner->declaration_node_id, syntax_kind::field_declaration);
            while ($member_cursor->advance())
            {
                $field = $member_cursor->current();
                $member = Syntax_Access::field_declaration_parts($tree, $field);
                $name = $this->text($member->variable_id);
                if (isset($fields[$name]) || isset($this->parameter_names[substr($name, 1)])) {
                    $this->fail($field, 'Duplicate field or conflicting template parameter');
                }
                $fields[$name] = true;
                $this->annotation($member->type_syntax_id, 0, 'field');
                $this->expression($member->extent_id, 0);
            }
        }
        elseif ($kind === syntax_kind::constant_declaration) {
            $this->constant_initializer($id, 0);
        }
    }

    /** Earlier parameters are visible in later value-parameter annotations; duplicates never shadow. */
    private function template_scope(int $list): void
    {
        $tree = $this->owner->frontend->syntax;
        for ($id = $tree->nodes[$list - 1]->first_child_id; $id !== 0; $id = $tree->nodes[$id - 1]->next_sibling_id)
        {
            $parts = Syntax_Access::template_parameter_parts($tree, $id);
            $name = $this->text($parts->name_id);
            if (($name === $this->owner->name) || isset($this->parameter_names[$name])) {
                $this->fail($parts->name_id, "Duplicate or conflicting template parameter '$name'");
            }
            if ($parts->type_syntax_id !== 0) {
                $this->annotation($parts->type_syntax_id, 0, 'template parameter');
            }
            $this->parameter_names[$name] = count($this->template_parameters);
            $this->template_parameters[] = new template_parameter($id, $parts->name_id, $parts->type_syntax_id);
        }
    }

    /** Resolve a type syntax subtree through the same iterative name traversal as value expressions. */
    private function annotation(int $id, int $scope, string $description): void
    {
        $this->expression($id, $scope, 0, name_role::type, $description);
    }

    /** Select a scoped parameter/constant or a global declaration; an unknown name is never marked dependent. */
    private function bind_name(int $id, name_role $role, int $scope, string $description = 'annotation'): name_binding
    {
        $name = $this->text($id);
        $position = $this->parameter_names[$name] ?? null;
        if ($position !== null)
        {
            $parameter = $this->template_parameters[$position];
            $type = $parameter->type_syntax_id === 0;
            if (($role === name_role::type_family) || (($role === name_role::type) !== $type)) {
                $this->fail($id, "Template parameter '$name' has the wrong name role");
            }
            $binding = new name_binding($id, $role, reference_kind::template_parameter, $position);
        }
        else
        {
            $constant = $this->find_constant($name, $scope);
            if ($constant !== 0) {
                if ($role !== name_role::value) {
                    $this->fail($id, "Constant '$name' is not a type");
                }
                $binding = new name_binding($id, $role, reference_kind::local_constant, $constant);
            }
            else {
                $binding = Declaration_Lookup::find($this->symbols, $this->catalog, $this->owner->namespace_name, $name, $id, $role);
            }
        }
        if ($binding === null) {
            $this->fail($id, $role === name_role::value ? "Unknown constant or template parameter '$name'"
                : "Unknown or unsupported $description type '$name'");
        }
        if ((($binding->kind === reference_kind::local_constant) && ($binding->target === $this->initializing_constant))
            || (($binding->kind === reference_kind::project_constant) && ($binding->target === $this->owner->symbol_id))) {
            $this->fail($id, "Constant '$name' cannot read itself in its initializer");
        }
        $this->name_bindings[] = $binding;
        return $binding;
    }

    /** Find the nearest preceding block constant; inactive sibling scopes never participate. */
    private function find_constant(string $name, int $scope): int
    {
        for ($id = $scope; $id !== 0; $id = $this->scopes[$id - 1]->parent_scope_id) {
            $constant = $this->constant_names[$id][$name] ?? 0;
            if ($constant !== 0) {
                return $constant;
            }
        }
        return 0;
    }

    /** Keep local constant scopes separate from runtime locals and template parameter slots. */
    private function constant_declaration(int $id, int $scope): void
    {
        $parts = Syntax_Access::constant_parts($this->owner->frontend->syntax, $id);
        $name = $this->text($parts->name_id);
        if (isset($this->parameter_names[$name]) || isset($this->constant_names[$scope][$name])) {
            $this->fail($parts->name_id, "Duplicate or conflicting constant '$name'");
        }
        $this->constants[] = new scoped_constant($id, $scope);
        $this->constant_names[$scope][$name] = $id;
        $this->initializing_constant = $id;
        $this->constant_initializer($id, $scope);
        $this->initializing_constant = 0;
    }

    /** Bind an optional annotation and initializer without computing the constant value. */
    private function constant_initializer(int $id, int $scope): void
    {
        $parts = Syntax_Access::constant_parts($this->owner->frontend->syntax, $id);
        if ($parts->type_syntax_id !== 0) {
            $this->annotation($parts->type_syntax_id, $scope, 'constant');
        }
        $this->expression($parts->initializer_id, $scope);
    }
}
