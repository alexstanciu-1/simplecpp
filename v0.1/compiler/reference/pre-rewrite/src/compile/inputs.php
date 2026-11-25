<?php
declare(strict_types=1);

/*
 * Role: Choose initial/full work and the supported incremental gate.
 * Used by: Compiler_Session::compile()
 * Call map:
 *   Input_Selection::select(); supports_increment()
 *     -> [action] classify configuration/contract changes
 */

namespace compile;

/** @compiler-internal Compile-owned refresh policy; stages report facts and do not choose this policy. */
class Input_Selection
{
    // Initial work selection; the coordinator owns when this decision is made.
    /**
     * @compiler-internal Create the initial update decision from manifest path/directory/exact content and initial status; no input mutation.
     */
    public static function select(\read_manifest\Project_Manifest $previous, \read_manifest\Project_Manifest $current, bool $initial): Update_Context
    {
        $update = new Update_Context();
        $update->full_rebuild = ($initial)
            || ($current->path !== $previous->path)
            || ($current->directory !== $previous->directory)
            || ($current->content !== $previous->content);
        return $update;
    }

    // First increment category for every request: unchanged callable definitions with
    // changed executable child content. All other catalog changes select full work.
    /**
     * @compiler-internal Read a completed symbol change catalog; accept only unchanged callable definitions with established child-change facts.
     * False requests full work through the common stages; it does not modify the catalog.
     */
    public static function supports_increment(\collect_symbols\Symbol_Refresh $symbols): bool
    {
        foreach ($symbols->changes as $change)
        {
            if (($change->own_status !== \collect_symbols\change_status::unchanged)
                || ($change->children_changed === null)
                || (!in_array($change->current?->kind, [\collect_symbols\symbol_kind::function_symbol, \collect_symbols\symbol_kind::template_function,
                            \collect_symbols\symbol_kind::file_entry], true))) {
                return false;
            }
        }
        return true;
    }
}
