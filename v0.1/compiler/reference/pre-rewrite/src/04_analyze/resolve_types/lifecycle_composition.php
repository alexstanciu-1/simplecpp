<?php
declare(strict_types=1);

/*
 * Role: Derive complete aggregate lifecycle contracts from accepted constituents.
 * Call map: Record_Definitions / Type_Cache -> Lifecycle_Composition::derive()
 *   Source_Export_Preparation -> Lifecycle_Composition::complete_operation()
 * Output: compact type-owned plans; no syntax, target offsets or per-element expansion.
 */
namespace resolve_types;

use type_model\construction_kind;
use type_model\copy_kind;
use type_model\assignment_kind;
use type_model\cleanup_kind;
use type_model\lifecycle_operation_kind;
use type_model\Type_Store;

final class Lifecycle_Composition
{
    /** Derive independent capabilities, retaining one direct member reference per field or array element type. */
    public static function derive(Type_Store $types, int $id, array $members, int $repeat = 0, int $constructor_body = 0, int $destructor_body = 0, int $copy_body = 0, int $assignment_body = 0): \type_model\lifetime_contract
    {
        $construct = construction_kind::zero;
        $copy = copy_kind::value;
        $assignment = assignment_kind::value;
        $cleanup = cleanup_kind::none;
        foreach ($members as $type)
        {
            $life = $types->definition_for_type($type)->lifetime;
            if (($life->construction === construction_kind::unavailable) || ($construct === construction_kind::unavailable)) {
                $construct = construction_kind::unavailable;
            }
            elseif ($life->construction === construction_kind::construct) {
                $construct = construction_kind::construct;
            }
            if (($life->copy === copy_kind::unavailable) || ($copy === copy_kind::unavailable)) {
                $copy = copy_kind::unavailable;
            }
            elseif ($life->copy === copy_kind::construct) {
                $copy = copy_kind::construct;
            }
            if (($life->assignment === assignment_kind::unavailable) || ($assignment === assignment_kind::unavailable)) {
                $assignment = assignment_kind::unavailable;
            }
            elseif ($life->assignment === assignment_kind::call) {
                $assignment = assignment_kind::call;
            }
            if ($life->cleanup === cleanup_kind::destroy) {
                $cleanup = cleanup_kind::destroy;
            }
        }

        // A custom body supplements field behavior; it cannot supply missing field initialization.
        if (($constructor_body !== 0) && ($construct !== construction_kind::unavailable)) {
            $construct = construction_kind::construct;
        }
        // A user copy body starts from field defaults; it does not require memberwise copyability.
        if ($copy_body !== 0) {
            if ($construct === construction_kind::unavailable) {
                throw new \RuntimeException('Custom copy construction requires supported field default initialization');
            }
            $copy = copy_kind::construct;
        }
        if ($destructor_body !== 0) {
            $cleanup = cleanup_kind::destroy;
        }
        if ($assignment_body !== 0) {
            $assignment = assignment_kind::call;
        }
        // A value needing destruction must be constructed into owned storage, even when all fields default to zero.
        if (($construct === construction_kind::zero) && ($cleanup === cleanup_kind::destroy)) {
            $construct = construction_kind::construct;
        }
        // User declarations suppress implicit movement; composed cleanup alone does not.
        $expiring = \type_model\expiring_construction::value;
        if (($destructor_body !== 0) || ($copy_body !== 0) || ($assignment_body !== 0)) {
            $expiring = $copy === copy_kind::unavailable ? \type_model\expiring_construction::unavailable : \type_model\expiring_construction::copy;
        }
        else
        {
            foreach ($members as $type)
            {
                $member = $types->definition_for_type($type)->lifetime;
                if ($member->expiring === \type_model\expiring_construction::unavailable) {
                    $expiring = \type_model\expiring_construction::unavailable;
                    break;
                }
                if (($member->expiring === \type_model\expiring_construction::construct)
                    || (($member->expiring === \type_model\expiring_construction::copy) && ($member->copy === copy_kind::construct))) {
                    $expiring = \type_model\expiring_construction::construct;
                }
            }
        }
        return new \type_model\lifetime_contract(
            copy: $copy,
            cleanup: $cleanup,
            destructor: $cleanup === cleanup_kind::destroy
                ? self::operation($types, $id, $members, lifecycle_operation_kind::destroy, $repeat, $destructor_body) : null,
            copy_constructor: $copy === copy_kind::construct
                ? self::operation($types, $id, $members, lifecycle_operation_kind::copy_construct, $repeat, $copy_body) : null,
            construction: $construct,
            default_constructor: $construct === construction_kind::construct
                ? self::operation($types, $id, $members, lifecycle_operation_kind::default_construct, $repeat, $constructor_body) : null,
            assignment: $assignment,
            copy_assignment: $assignment === assignment_kind::call
                ? self::operation($types, $id, $members, lifecycle_operation_kind::copy_assign, $repeat, $assignment_body) : null,
            expiring: $expiring,
            move_constructor: $expiring === \type_model\expiring_construction::construct
                ? self::operation($types, $id, $members, lifecycle_operation_kind::move_construct, $repeat) : null);
    }

    /** Materialize a permitted complete record operation, including primitive fields or empty cleanup.
     * Missing semantic support remains null; this does not invent movement or change type permissions. */
    public static function complete_operation(Type_Store $types, int $id,
        lifecycle_operation_kind $role): ?\type_model\source_lifecycle_operation
    {
        $definition = $types->definition_for_type($id);
        if ($definition->representation->kind !== \type_model\representation_kind::structure) {
            throw new \LogicException('Complete source export operation requires a record');
        }
        $life = $definition->lifetime;
        $existing = $life->operation($role);
        if ($existing !== null) {
            return $existing instanceof \type_model\source_lifecycle_operation ? $existing : null;
        }
        $primitive = match ($role) {
            lifecycle_operation_kind::default_construct => $life->construction === construction_kind::zero,
            lifecycle_operation_kind::copy_construct => $life->copy === copy_kind::value,
            lifecycle_operation_kind::copy_assign => $life->assignment === assignment_kind::value,
            lifecycle_operation_kind::destroy => $life->cleanup === cleanup_kind::none,
            lifecycle_operation_kind::move_construct => false,
        };
        if (!$primitive) {
            return null;
        }
        $members = [];
        for ($index = 0; $index < $definition->representation->payload->count; ++$index) {
            $members[] = $types->field_for($id, $index)->type_id;
        }
        return self::operation($types, $id, $members, $role, 0);
    }

    /** Select direct constituent operations now; emission must not infer semantic capabilities from storage. */
    private static function operation(Type_Store $types, int $id, array $members,
        lifecycle_operation_kind $role, int $repeat, int $body = 0): \type_model\source_lifecycle_operation
    {
        $plan = [];
        $order = $role->composition($body !== 0);
        $member_role = $order->member_kind;
        if ($member_role === null) {
            $members = [];
        }
        foreach ($members as $index => $type)
        {
            $life = $types->definition_for_type($type)->lifetime;
            $selected_role = ($member_role === lifecycle_operation_kind::move_construct)
                && ($life->expiring === \type_model\expiring_construction::copy) ? lifecycle_operation_kind::copy_construct : $member_role;
            $operation = $life->operation($selected_role);
            if (($role !== lifecycle_operation_kind::destroy) || ($operation !== null)) {
                $plan[] = new \type_model\lifecycle_member($type, $index, $operation, $selected_role);
            }
        }
        if ($order->reverse_members) {
            $plan = array_reverse($plan);
        }
        // Type_Store owns these non-recycled IDs within one linked compilation lineage.
        $link = '__scpp_lifecycle_type_' . $id . '_' . $role->value;
        return new \type_model\source_lifecycle_operation($id, $link, $role, $plan, $repeat, body_symbol_id: $body);
    }
}
