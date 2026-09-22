<?php
declare(strict_types=1);

/*
 * Role: Validate local names and retained local type dependencies.
 * Used by: Local_Type_Resolver and Local_Type_Join
 * Call map:
 *   Local_Type_Validity::is_current()
 *     -> [action] compare exact bindings, definitions and type associations
 */

namespace resolve_types;

use type_model\Type_Store;

use collect_symbols\symbol_record;
use resolve_symbols\Resolution_Set;
use resolve_symbols\Symbol_Resolution;

/** @compiler-internal Shared callable bindings and local type validity for selection, workers and joining. */
class Local_Type_Validity
{
    /** Require the current name-resolution owner before selecting local type work. */
    public static function names_for(symbol_record $owner, Resolution_Set $names): Symbol_Resolution
    {
        $result = $names->for_symbol($owner->symbol_id);
        if (($result === null) || ($result->syntax !== $owner->frontend->syntax)
            || (($result->scopes[0] ?? null)?->block_node_id !== $owner->body_node_id)) {
            throw new \LogicException('Local types require current callable name bindings');
        }
        return $result;
    }

    /** Check retained local types against current syntax, bindings and representations without changing either input. */
    public static function is_current(?Type_Resolution $previous, Type_Store $types, Symbol_Resolution $names, ?\instantiate\instance_context $instance = null): bool
    {
        $local = $previous?->locals_for($instance?->context_id ?? $names->symbol_id);
        if (($local === null) || ($local->instance !== $instance) || ($local->names !== $names)) {
            return false;
        }
        try {
            foreach ($local->type_ids as $id) {
                if (($previous->types->type_by_id($id) !== $types->type_by_id($id)) || $types->needs_representation($id)) {
                    return false;
                }
            }
        }
        catch (\OutOfBoundsException $error) {
            return false;
        }
        return true;
    }
}
