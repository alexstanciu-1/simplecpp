<?php
declare(strict_types=1);

/*
 * Role: Traverse scoped statements and bind uses.
 * Used by: Resolution_Worker (private trait methods on this owner)
 * Call map:
 *   statements()
 *     -> statement() [each statement]
 */

namespace resolve_symbols;

use parse\Syntax_Access;
use parse\syntax_kind;

/**
 * @compiler-internal Resolution_Worker statement traversal and handlers.
 * The driver owns its cursor stack and retires only scopes owned by a cursor;
 * handlers return a continuation or null after completing the statement's work.
 */
trait Statement_Resolution
{
    /** Walk nested statement scopes with private cursors and retire bindings at their owning exit. */
    private function statements(scope_cursor $root): void
    {
        $tree = $this->owner->frontend->syntax;
        $pending = [$root];
        while ($pending !== [])
        {
            $cursor = $pending[count($pending) - 1];
            $id = $cursor->next_statement_id;
            if ($id === 0) {
                if ($cursor->owns_scope) {
                    unset($this->names[$cursor->scope_id], $this->constant_names[$cursor->scope_id]);
                }
                array_pop($pending);
                continue;
            }
            $cursor->next_statement_id = $tree->nodes[$id - 1]->next_sibling_id;
            $next = $this->statement($id, $cursor->scope_id);
            if ($next !== null) {
                $pending[] = $next;
            }
        }
    }

    /** Dispatch statement name uses and scope entry without performing type or runtime decisions. */
    private function statement(int $id, int $scope_id): ?scope_cursor
    {
        $node = $this->owner->frontend->syntax->nodes[$id - 1];
        switch ($node->kind)
        {
            case syntax_kind::constexpr_if_statement:
            case syntax_kind::consteval_if_statement:
            case syntax_kind::if_statement:
            case syntax_kind::while_statement:
                return $this->control_statement($id, $scope_id);
            case syntax_kind::block:
                return $this->enter($id, $scope_id);
            case syntax_kind::local_declaration:
                $this->local_declaration($id, $scope_id);
                break;
            case syntax_kind::constant_declaration:
                $this->constant_declaration($id, $scope_id);
                break;
            case syntax_kind::assignment_statement:
                $this->assignment_statement($id, $scope_id);
                break;
            case syntax_kind::echo_statement:
                for ($child = $node->first_child_id; $child !== 0; $child = $this->owner->frontend->syntax->nodes[$child - 1]->next_sibling_id) {
                    $this->expression($child, $scope_id);
                }
                break;
            case syntax_kind::return_statement:
            case syntax_kind::expression_statement:
                $this->expression_statement($id, $scope_id);
                break;
            default:
                $this->fail($id, 'Unsupported body syntax for name resolution: ' . $node->kind->name);
        }
        return null;
    }

    /** Bind any condition and schedule both alternatives; template branches remain unevaluated. */
    private function control_statement(int $id, int $scope_id): scope_cursor
    {
        $parts = Syntax_Access::control_parts($this->owner->frontend->syntax, $id);
        if ((!$this->owner->is_template()) && ($parts->alternative !== 0)
            && ($this->owner->frontend->syntax->nodes[$parts->alternative - 1]->kind !== syntax_kind::block)) {
            $this->fail($parts->alternative, 'Else-if semantic resolution is not implemented; use a braced alternative');
        }
        $this->expression($parts->condition, $scope_id);

        // Body and alternative are siblings; blocks establish their own scopes;
        // this cursor only schedules them and must not retire the enclosing scope.
        return new scope_cursor($scope_id, $parts->body, false);
    }

    /** Resolve writes to a root local, retaining field selection for type checking. */
    private function assignment_statement(int $id, int $scope_id): void
    {
        $parts = Syntax_Access::assignment_parts($this->owner->frontend->syntax, $id);
        $root = Syntax_Access::place_root($this->owner->frontend->syntax, $parts->target_id);
        if ($root === 0) {
            $this->fail($parts->target_id, 'Assignment requires a local storage root');
        }
        $this->bind_local($root, $scope_id, local_access::write);
        foreach ($this->place_indices($parts->target_id) as $index) {
            $this->expression($index, $scope_id);
        }
        $this->expression($parts->value_id, $scope_id);
    }

    private function expression_statement(int $id, int $scope_id): void
    {
        $expression = Syntax_Access::statement_expression($this->owner->frontend->syntax, $id);
        if ($expression !== 0) {
            $this->expression($expression, $scope_id);
        }
    }
}
