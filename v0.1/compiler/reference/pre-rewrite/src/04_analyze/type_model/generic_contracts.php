<?php
declare(strict_types=1);

/*
 * Role: Default type-parameter acceptance, independent of source spelling or ABI.
 * Used by: template definition checking; instance workers and joins
 * Call map: Generic_Contracts::missing() -> [action] inspect accepted lifetime facts
 */
namespace type_model;

final class Generic_Contracts
{
    /** Return a missing supported operation; absence never invents trivial lifecycle behavior. */
    public static function missing(named_type_definition $type, generic_contract $contract): ?string
    {
        $life = $type->lifetime;
        if ($life === null) {
            return 'value lifetime';
        }
        if ($life->copy === copy_kind::unavailable) {
            return 'copy construction';
        }
        if ($life->assignment === assignment_kind::unavailable) {
            return 'copy assignment';
        }
        return null;
    }
}
