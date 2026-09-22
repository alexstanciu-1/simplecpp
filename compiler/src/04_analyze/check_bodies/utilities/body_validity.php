<?php
declare(strict_types=1);

/*
 * Role: Validate retained checked-body dependencies.
 * Used by: Body_Checker::select(); Body_Join::join()
 * Call map:
 *   Body_Validity::is_current()
 *     -> [action] compare bindings, signatures and referenced types
 */

namespace check_bodies;

use collect_symbols\symbol_record;
use resolve_symbols\Resolution_Set;
use resolve_types\Type_Resolution;

/** @compiler-internal Shared checked-body validity for selection and joining. */
class Body_Validity
{
    /** Allow body reuse only while its owner, names, local types and recorded dependencies remain current. */
    public static function is_current(?Checked_Body $body, symbol_record $symbol, Resolution_Set $names, Type_Resolution $types, ?\instantiate\instance_context $instance = null): bool
    {
        if (($body === null) || ($body->instance !== $instance) || ($body->owner !== $symbol) || ($body->names !== $names->for_symbol($symbol->symbol_id))
            || ($body->local_types !== $types->locals_for($instance?->context_id ?? $symbol->symbol_id))
            || (!isset($body->signature_dependencies[$instance?->context_id ?? $symbol->symbol_id]))) {
            return false;
        }
        try
        {
            foreach ($body->type_dependencies as $id => $record) {
                if ($types->types->type_by_id($id) !== $record) {
                    return false;
                }
            }
            foreach ($body->signature_dependencies as $id => $dependency)
            {
                $signature = $types->for_callable($id);
                if (($signature === null) || ($signature->representation_id !== $dependency->representation_id)
                    || ($signature->external !== $dependency->external) || ($signature->storage !== $dependency->storage)
                    || ($types->types->representation_by_id($signature->representation_id) !== $dependency->representation)) {
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
