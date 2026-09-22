<?php
declare(strict_types=1);
namespace resolve_symbols;
trait Name_Resolution {
    private function definition(): void {
        $id = (int)$this->owner->declaration->declaration_node_id; if ($id === 0) { return; }
        $tree = $this->owner->frontend->tree;
        if ($this->owner->is_template()) { $this->template_scope((int)$this->owner->declaration->template_parameters_node_id); }
        $id = \parse\Syntax_Access::underlying_declaration($tree,$id); $kind = (int)$tree->row($id)->kind;
        if ($kind === \parse\SYNTAX_FUNCTION_DECLARATION) {
            $parts = \parse\Syntax_Access::function_parts($tree,$id); $this->annotation((int)$parts->return_type_id,0,'return');
        } elseif ($kind === \parse\SYNTAX_STRUCT_DECLARATION) {
            $parts = \parse\Syntax_Access::struct_parts($tree,$id);
            if ($this->catalog->find_type($this->owner->name,'') !== null) { $this->fail((int)$parts->name_id,'Duplicate source/provider type: ' . $this->owner->name); }
            $fields /** hash<bool> */ = [];
            $cursor = \parse\Syntax_Access::struct_members($tree,(int)$this->owner->declaration->declaration_node_id,\parse\SYNTAX_FIELD_DECLARATION);
            while ($cursor->advance()) {
                $field = $cursor->current(); $member = \parse\Syntax_Access::field_declaration_parts($tree,$field);
                $name = $this->text((int)$member->variable_id); $bare = string_byte_slice($name,1,string_byte_len($name)-1);
                if (isset($fields[$name]) || isset($this->parameter_names[$bare])) { $this->fail($field,'Duplicate field or conflicting template parameter'); }
                $fields[$name] = true; $this->annotation((int)$member->type_syntax_id,0,'field');
                $this->expression((int)$member->extent_id,0,0,\resolve_symbols\NAME_VALUE,'annotation');
            }
        } elseif ($kind === \parse\SYNTAX_CONSTANT_DECLARATION) { $this->constant_initializer($id,0); }
    }
    private function template_scope(int $list): void {
        $tree = $this->owner->frontend->tree; $id = (int)$tree->row($list)->first_child;
        while ($id !== 0) {
            $parts = \parse\Syntax_Access::template_parameter_parts($tree,$id); $name = $this->text((int)$parts->name_id);
            if (($name === $this->owner->name) || isset($this->parameter_names[$name])) { $this->fail((int)$parts->name_id,"Duplicate or conflicting template parameter '" . $name . "'"); }
            if ((int)$parts->type_syntax_id !== 0) { $this->annotation((int)$parts->type_syntax_id,0,'template parameter'); }
            $this->parameter_names[$name] = q_count($this->template_parameters);
            $row = new Template_Parameter(); $row->declaration_node_id = $id; $row->name_node_id = $parts->name_id; $row->type_syntax_id = $parts->type_syntax_id;
            $row->contract = (int)$parts->type_syntax_id === 0 ? \type_model\GENERIC_COPYABLE_VALUE : \type_model\GENERIC_NONE;
            $this->template_parameters[] = $row; $id = (int)$tree->row($id)->next_sibling;
        }
    }
    private function annotation(int $id, int $scope, string $description): void { $this->expression($id,$scope,0,\resolve_symbols\NAME_TYPE,$description); }
    /** Resolve scoped targets before global lookup without a dynamic union. */
    private function lookup_name(int $id, int $role, int $scope): ?Name_Binding {
        $name = $this->text($id);
        if (isset($this->parameter_names[$name])) {
            $position = $this->parameter_names[$name]; $parameter = $this->template_parameters[$position];
            $type = (int)$parameter->type_syntax_id === 0;
            if (($role === \resolve_symbols\NAME_TYPE_FAMILY) || (($role === \resolve_symbols\NAME_TYPE) !== $type)) { $this->fail($id,"Template parameter '" . $name . "' has the wrong name role"); }
            return new Name_Binding($id,$role,\resolve_symbols\REFERENCE_TEMPLATE_PARAMETER,$position,null);
        }
        $constant = $this->find_constant($name,$scope);
        if ($constant !== 0) {
            if ($role !== \resolve_symbols\NAME_VALUE) { $this->fail($id,"Constant '" . $name . "' is not a type"); }
            return new Name_Binding($id,$role,\resolve_symbols\REFERENCE_LOCAL_CONSTANT,$constant,null);
        }
        return Declaration_Lookup::find($this->symbols,$this->catalog,$name,$id,$role);
    }
    private function bind_name(int $id, int $role, int $scope, string $description): Name_Binding {
        $binding = $this->lookup_name($id,$role,$scope); $name = $this->text($id);
        if ($binding === null) {
            if ($role === \resolve_symbols\NAME_VALUE) { $this->fail($id,"Unknown constant or template parameter '" . $name . "'"); }
            $this->fail($id,'Unknown or unsupported ' . $description . " type '" . $name . "'");
        }
        if ((($binding->kind === \resolve_symbols\REFERENCE_LOCAL_CONSTANT) && ($binding->target_id === $this->initializing_constant))
            || (($binding->kind === \resolve_symbols\REFERENCE_PROJECT_CONSTANT) && ($binding->target_id === $this->owner->symbol_id))) { $this->fail($id,"Constant '" . $name . "' cannot read itself in its initializer"); }
        $this->name_bindings[] = $binding; return $binding;
    }
    private function find_constant(string $name, int $scope): int {
        $id = $scope;
        while ($id !== 0) { $target = $this->scope_names[$id-1]->constant($name); if ($target !== 0) { return $target; } $id = (int)$this->scopes[$id-1]->parent_scope_id; }
        return 0;
    }
    private function constant_declaration(int $id, int $scope): void {
        $parts = \parse\Syntax_Access::constant_parts($this->owner->frontend->tree,$id); $name = $this->text((int)$parts->name_id);
        if (isset($this->parameter_names[$name]) || ($this->scope_names[$scope-1]->constant($name) !== 0)) { $this->fail((int)$parts->name_id,"Duplicate or conflicting constant '" . $name . "'"); }
        $row = new Scoped_Constant(); $row->declaration_node_id = $id; $row->scope_id = $scope; $this->constants[] = $row;
        $this->scope_names[$scope-1]->add_constant($name,$id); $this->initializing_constant = $id;
        $this->constant_initializer($id,$scope); $this->initializing_constant = 0;
    }
    private function constant_initializer(int $id, int $scope): void {
        $parts = \parse\Syntax_Access::constant_parts($this->owner->frontend->tree,$id);
        if ((int)$parts->type_syntax_id !== 0) { $this->annotation((int)$parts->type_syntax_id,$scope,'constant'); }
        $this->expression((int)$parts->initializer_id,$scope,0,\resolve_symbols\NAME_VALUE,'annotation');
    }
}
