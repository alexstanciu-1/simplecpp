<?php
declare(strict_types=1);

/*
 * Role: Bind parameters and local declarations.
 * Used by: Resolution_Worker (private trait methods on this owner)
 * Call map:
 *   parameters(); local_declaration()
 *     -> declare_local()
 */

namespace resolve_symbols;

use collect_symbols\symbol_kind;
use parse\Syntax_Access;

/** @compiler-internal Resolution_Worker handlers for parameter and body-local declarations. */
trait Declaration_Resolution
{
    /** Bind source parameters in declaration order as the local-table prefix; file entries have none. */
    private function parameters(int $scope_id): void
    {
        // The receiver is an implicit borrowed parameter, anchored in the original member declaration.
        if ($this->owner->owner_symbol_id !== 0) {
            $this->locals[] = new local_record($this->owner->declaration_node_id, $scope_id, true);
            $this->names[$scope_id]['$this'] = 1;
        }
        // Parameters occupy the root scope before any body-local declarations.
        if (in_array($this->owner->kind, [symbol_kind::function_symbol, symbol_kind::template_function], true))
        {
            $tree = $this->owner->frontend->syntax;
            $parts = Syntax_Access::function_parts($tree, Syntax_Access::underlying_declaration($tree, $this->owner->declaration_node_id));
            for ($id = Syntax_Access::first_parameter($tree, $parts->parameters_id); $id !== 0; $id = $tree->nodes[$id - 1]->next_sibling_id) {
                $parameter = Syntax_Access::parameter_parts($tree, $id);
                $this->annotation($parameter->type_syntax_id, $scope_id, 'parameter');
                $this->declare_local($id, $parameter->variable_id, $scope_id);
            }
        }
    }

    private function local_declaration(int $id, int $scope_id): void
    {
        $parts = Syntax_Access::local_declaration_parts($this->owner->frontend->syntax, $id);
        $this->annotation($parts->type_syntax_id, $scope_id, 'local');
        $local_id = $this->declare_local($id, $parts->variable_id, $scope_id);

        // The new declaration hides outer names even in its initializer.
        // Reading it there is an error, not a read of an outer variable.
        $this->expression($parts->initializer_id, $scope_id, $local_id);
    }
}
