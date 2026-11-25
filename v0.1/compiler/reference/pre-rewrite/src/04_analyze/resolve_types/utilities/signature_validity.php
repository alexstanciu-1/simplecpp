<?php
declare(strict_types=1);

/*
 * Role: Validate retained signature dependencies.
 * Used by: Signature_Resolver and Signature_Join
 * Call map:
 *   Signature_Validity::is_current()
 *     -> Signature_Validity::same_type()
 */

namespace resolve_types;

use type_model\Type_Store;
use type_model\representation_kind;

use collect_symbols\symbol_record;

/** @compiler-internal Shared signature validity for type selection and joining. */
class Signature_Validity
{
    /** Check retained signature provenance, provider identity and shared type rows before incremental reuse. */
    public static function is_current(?Type_Resolution $previous, Type_Store $types, symbol_record $symbol, ?\instantiate\instance_context $instance = null, array $prepared = []): bool
    {
        $external = $symbol->external instanceof \type_model\storage_function ? $symbol->external
            : Callable_Inputs::external($symbol, $instance, $prepared);
        $signature = $previous?->for_callable($instance?->context_id ?? $symbol->symbol_id);
        if (($signature === null) || ($signature->instance !== $instance) || ($signature->syntax !== $symbol->frontend?->syntax) || (($signature->storage ?? $signature->external) !== $external)
            || ($signature->declaration_node_id !== $symbol->declaration_node_id) || ($signature->body_node_id !== $symbol->body_node_id)) {
            return false;
        }
        try
        {
            $old_shape = $previous->types->representation_by_id($signature->representation_id);
            $shape = $types->representation_by_id($signature->representation_id);
            if (($shape !== $old_shape) || ($shape->kind !== representation_kind::function_signature)) {
                return false;
            }
            $return_type = $shape->payload->return_type;
            if (!self::same_type($previous->types, $types, $return_type)) {
                return false;
            }
            for ($i = 0; $i < $shape->payload->count; ++$i) {
                if (!self::same_type($previous->types, $types, $types->member_at($shape->payload->first + $i)->type_id)) {
                    return false;
                }
            }
            return true;
        }
        catch (\OutOfBoundsException $error) {
            return false;
        }
    }

    private static function same_type(Type_Store $previous, Type_Store $current, int $id): bool
    {
        return ($previous->type_by_id($id) === $current->type_by_id($id)) && (!$current->needs_representation($id));
    }
}
