<?php
declare(strict_types=1);
namespace resolve_symbols;
trait Declaration_Resolution {
    private function parameters(int $scope): void {
        if ($this->owner->owner_symbol_id !== 0) {
            $row = new Local_Record(); $row->declaration_node_id = $this->owner->source_fact()->declaration_node_id; $row->scope_id = $scope; $row->receiver = true;
            $this->locals[] = $row; $this->scope_names[$scope-1]->add_local('$this',1);
        }
        $kind = $this->owner->kind();
        if (($kind === \collect_symbols\SYMBOL_FUNCTION) || ($kind === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION)) {
            $tree = $this->owner->source_frontend()->tree;
            $parts = \parse\Syntax_Access::function_parts($tree,\parse\Syntax_Access::underlying_declaration($tree,(int)$this->owner->source_fact()->declaration_node_id));
            $id = \parse\Syntax_Access::first_parameter($tree,(int)$parts->parameters_id);
            while ($id !== 0) {
                $parameter = \parse\Syntax_Access::parameter_parts($tree,$id);
                $this->annotation((int)$parameter->type_syntax_id,$scope,'parameter');
                $this->declare_local($id,(int)$parameter->variable_id,$scope);
                $id = (int)$tree->row($id)->next_sibling;
            }
        }
    }
    private function local_declaration(int $id, int $scope): void {
        $parts = \parse\Syntax_Access::local_declaration_parts($this->owner->source_frontend()->tree,$id);
        $this->annotation((int)$parts->type_syntax_id,$scope,'local');
        $local = $this->declare_local($id,(int)$parts->variable_id,$scope);
        $this->expression((int)$parts->initializer_id,$scope,$local,\resolve_symbols\NAME_VALUE,'annotation');
    }
}
