<?php
declare(strict_types=1);

/*
 * Role: Compare one fixed symbol pair.
 * Used by: Symbol_Comparer::run()
 * Call map: Comparison_Worker::compare() -> Syntax_Comparer::equal()
 */

namespace collect_symbols;

use parse\Syntax_Comparer;

// Catalog facts only. Work selection and downstream reactions have other owners.
/**
 * @compiler-api Definition/child-change cataloging process used by compile; reads syntax through
 * parse contracts and does not decide downstream work or replace current AST bindings.
 */
class Comparison_Worker
{
    /**
     * @compiler-api Compare one fixed matched pair; return own_status and child-content summary.
     * Throws for invalid/unsupported comparison shapes; never changes symbol/AST records.
     */
    public static function compare(symbol_change $task): symbol_change
    {
        $previous = $task->previous;
        $current = $task->current;
        if (($previous === null) || ($current === null)) {
            throw new \LogicException('Comparison requires matched symbols');
        }
        if (($previous->external !== null) || ($current->external !== null)) {
            return new symbol_change($previous, $current,
                ($previous->external === $current->external) ? change_status::unchanged : change_status::changed, children_changed: false);
        }
        $left = Declaration_Syntax::definition_nodes($previous);
        $right = Declaration_Syntax::definition_nodes($current);
        $own_equal = ($previous->kind === $current->kind) && ($previous->name === $current->name)
            && ($previous->namespace_name === $current->namespace_name) && ($previous->owner_symbol_id === $current->owner_symbol_id)
            && (count($left) === count($right));
        for ($i = 0; ($own_equal) && ($i < count($left)); $i++) {
            $own_equal = Syntax_Comparer::equal($previous->frontend, $left[$i], $current->frontend, $right[$i]);
        }

        // For the currently supported callables, the executable body is their
        // tracked child content. No per-statement change records are introduced.
        $children_equal = Syntax_Comparer::equal($previous->frontend, $previous->body_node_id,
            $current->frontend, $current->body_node_id);
        return new symbol_change($previous, $current, $own_equal ? change_status::unchanged : change_status::changed,
            children_changed: !$children_equal);
    }
}
