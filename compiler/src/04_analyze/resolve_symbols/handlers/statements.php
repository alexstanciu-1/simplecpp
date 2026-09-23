<?php
declare(strict_types=1);
namespace resolve_symbols;
trait Statement_Resolution {
    private function statements(Scope_Cursor $root): void {
        $pending /** vector<Scope_Cursor> */ = [$root]; $used = 1; $tree = $this->owner->source_frontend()->tree;
        while ($used > 0) {
            $cursor = $pending[$used-1]; $id = $cursor->next_statement_id;
            if ($id === 0) {
                if ($cursor->owns_scope) { $this->scope_names[$cursor->scope_id-1]->retire(); }
                $used = $used - 1; continue;
            }
            $cursor->next_statement_id = (int)$tree->row($id)->next_sibling;
            $next = $this->statement($id,$cursor->scope_id);
            if ($next !== null) {
                if ($used === q_count($pending)) { $pending[] = $next; } else { $pending[$used] = $next; }
                $used++;
            }
        }
    }
    private function statement(int $id, int $scope): ?Scope_Cursor {
        $tree = $this->owner->source_frontend()->tree; $node = $tree->row($id); $kind = (int)$node->kind;
        if (($kind === \parse\SYNTAX_IF_STATEMENT) || ($kind === \parse\SYNTAX_WHILE_STATEMENT)
            || ($kind === \parse\SYNTAX_CONSTEXPR_IF_STATEMENT) || ($kind === \parse\SYNTAX_CONSTEVAL_IF_STATEMENT)) { return $this->control_statement($id,$scope); }
        if ($kind === \parse\SYNTAX_BLOCK) { return $this->enter($id,$scope); }
        if ($kind === \parse\SYNTAX_LOCAL_DECLARATION) { $this->local_declaration($id,$scope); }
        elseif ($kind === \parse\SYNTAX_CONSTANT_DECLARATION) { $this->constant_declaration($id,$scope); }
        elseif ($kind === \parse\SYNTAX_ASSIGNMENT_STATEMENT) { $this->assignment_statement($id,$scope); }
        elseif ($kind === \parse\SYNTAX_ECHO_STATEMENT) {
            $child = (int)$node->first_child;
            while ($child !== 0) { $this->expression($child,$scope,0,\resolve_symbols\NAME_VALUE,'annotation'); $child = (int)$tree->row($child)->next_sibling; }
        } elseif (($kind === \parse\SYNTAX_RETURN_STATEMENT) || ($kind === \parse\SYNTAX_EXPRESSION_STATEMENT)) {
            $this->expression(\parse\Syntax_Access::statement_expression($tree,$id),$scope,0,\resolve_symbols\NAME_VALUE,'annotation');
        } else { $this->fail($id,'Unsupported body syntax for name resolution: ' . $kind); }
        return null;
    }
    private function control_statement(int $id, int $scope): Scope_Cursor {
        $tree = $this->owner->source_frontend()->tree; $parts = \parse\Syntax_Access::control_parts($tree,$id);
        if (!$this->owner->is_template()) {
            if ((int)$parts->alternative !== 0) {
                if ((int)$tree->row((int)$parts->alternative)->kind !== \parse\SYNTAX_BLOCK) { $this->fail((int)$parts->alternative,'Else-if semantic resolution is not implemented; use a braced alternative'); }
            }
        }
        $this->expression((int)$parts->condition,$scope,0,\resolve_symbols\NAME_VALUE,'annotation');
        // This schedules sibling branches but does not own or retire the parent scope.
        return new Scope_Cursor($scope,(int)$parts->body,false);
    }
    private function assignment_statement(int $id, int $scope): void {
        $parts = \parse\Syntax_Access::assignment_parts($this->owner->source_frontend()->tree,$id);
        $root = \parse\Syntax_Access::place_root($this->owner->source_frontend()->tree,(int)$parts->target_id);
        if ($root === 0) { $this->fail((int)$parts->target_id,'Assignment requires a local storage root'); }
        $this->bind_local($root,$scope,\resolve_symbols\LOCAL_WRITE,0);
        $indices = $this->place_indices((int)$parts->target_id);
        $position = q_count($indices);
        while ($position > 0) { $position = $position - 1; $this->expression($indices[$position],$scope,0,\resolve_symbols\NAME_VALUE,'annotation'); }
        $this->expression((int)$parts->value_id,$scope,0,\resolve_symbols\NAME_VALUE,'annotation');
    }
}
