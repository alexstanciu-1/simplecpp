<?php
declare(strict_types=1);

/*
 * Role: Private lifecycle processing for Package_Adapter.
 * Used by: Package_Adapter; methods execute on that single adapter owner
 * Call map: Package_Adapter::lifetime() -> default_constructor(); lifecycle_operation()
 *   lifecycle_operation() -> assignment_contract() [if copy assignment]
 * Output: validated compiler contracts; no canonical type IDs allocated here.
 */

namespace load_runtime;

use type_model\cleanup_kind;
use type_model\copy_kind;
use type_model\lifecycle_operation_kind;
use type_model\lifetime_contract;
use type_model\runtime_lifecycle_operation;

trait Lifecycle_Import
{
    /** Import independent construction, assignment and cleanup capabilities for an inline type. */
    private static function lifetime(array $type, array $operations, string $provider): lifetime_contract
    {
        $lifecycle = $type['lifecycle'] ?? [];
        $destructor = null;
        if (($lifecycle['cleanup'] ?? null) === 'none') {
            if (($type['cpp_traits']['trivially_destructible'] ?? null) !== true) {
                throw new \RuntimeException('Exposed inline type requires verified cleanup none contract');
            }
        }
        else {
            if (isset($lifecycle['cleanup'])) {
                throw new \RuntimeException('Unsupported runtime cleanup policy');
            }
            $destructor = self::lifecycle_operation($type, $operations, $provider, lifecycle_operation_kind::destroy);
        }

        // C++ copy availability alone grants no language copy capability.
        $copy = isset($lifecycle['copy_construct'])
            ? self::lifecycle_operation($type, $operations, $provider, lifecycle_operation_kind::copy_construct) : null;
        $move = isset($lifecycle['move_construct'])
            ? self::lifecycle_operation($type, $operations, $provider, lifecycle_operation_kind::move_construct) : null;
        $assign = isset($lifecycle['copy_assign'])
            ? self::lifecycle_operation($type, $operations, $provider, lifecycle_operation_kind::copy_assign) : null;
        $default = isset($lifecycle['default_construct'])
            ? self::default_constructor($type, $operations, $provider) : null;
        return new lifetime_contract($copy === null ? copy_kind::unavailable : copy_kind::construct,
            $destructor === null ? cleanup_kind::none : cleanup_kind::destroy, $destructor, $copy,
            $default === null ? \type_model\construction_kind::unavailable : \type_model\construction_kind::construct, $default,
            assignment: $assign === null ? \type_model\assignment_kind::unavailable : \type_model\assignment_kind::call,
            copy_assignment: $assign,
            expiring: $move === null ? \type_model\expiring_construction::unavailable : \type_model\expiring_construction::construct,
            move_constructor: $move);
    }

    /** Import an explicitly selected implicit no-argument construction operation. */
    private static function default_constructor(array $type, array $operations, string $provider): runtime_lifecycle_operation
    {
        $row = $operations[$type['lifecycle']['default_construct']] ?? [];
        $abi = $row['abi'] ?? [];
        $result = $row['result'] ?? [];
        if ((($row['kind'] ?? null) !== 'construct') || (($row['type'] ?? null) !== $type['id'])
            || isset($row['expose_as']) || array_key_exists('allocation_effect', $row) || (($row['parameters'] ?? null) !== [])
            || (($row['calling_convention'] ?? null) !== 'ccc') || (($row['error_policy'] ?? null) !== 'terminate')
            || (($row['exception_boundary'] ?? null) !== 'caught_in_bridge')
            || (($row['storage_precondition'] ?? null) !== 'aligned_uninitialized_storage')
            || (($result['type'] ?? null) !== $type['id']) || (($result['ownership'] ?? null) !== 'owned')
            || (($result['passing'] ?? null) !== 'caller_storage') || (($result['abi_index'] ?? null) !== 0)
            || (($abi['return_type'] ?? null) !== 'void') || (($abi['return_attributes'] ?? null) !== '')
            || (count($abi['parameters'] ?? []) !== 1)) {
            throw new \RuntimeException('Unsupported runtime default construction contract');
        }
        self::address_abi($abi['parameters'][0]);
        $link = $row['symbol'] ?? null;
        if (!is_string($link) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $link)) {
            throw new \RuntimeException('Invalid runtime lifecycle symbol');
        }
        return new runtime_lifecycle_operation($provider, $row['id'], $link, 'ccc', lifecycle_operation_kind::default_construct);
    }

    /** Validate one implicit operation's shared ABI boundary and its role-specific ownership effects. */
    private static function lifecycle_operation(array $type, array $operations, string $provider,
        lifecycle_operation_kind $kind): runtime_lifecycle_operation
    {
        $operation = $operations[$type['lifecycle'][$kind->value] ?? ''] ?? null;
        $parameter = $operation['parameters'][0] ?? [];
        $abi = $operation['abi'] ?? [];
        $valid = ($operation !== null) && (($operation['kind'] ?? null) === $kind->value)
            && (($operation['type'] ?? null) === $type['id']) && !isset($operation['expose_as'])
            && !array_key_exists('allocation_effect', $operation)
            && (($operation['calling_convention'] ?? null) === 'ccc')
            && (($operation['error_policy'] ?? null) === 'terminate')
            && (($operation['exception_boundary'] ?? null) === 'caught_in_bridge')
            && (count($operation['parameters'] ?? []) === ($kind === lifecycle_operation_kind::copy_assign ? 2 : 1))
            && (($parameter['type'] ?? null) === $type['id'])
            && (($abi['return_type'] ?? null) === 'void') && (($abi['return_attributes'] ?? null) === '');

        // Assignment updates live storage; construction initializes it and destruction ends its lifetime.
        if ($kind === lifecycle_operation_kind::destroy) {
            $valid = ($valid) && (($operation['storage_precondition'] ?? null) === 'live_owned_object')
                && (($operation['storage_after'] ?? null) === 'uninitialized_caller_storage')
                && array_key_exists('result', $operation) && ($operation['result'] === null)
                && (($parameter['ownership'] ?? null) === 'consumed') && (($parameter['passing'] ?? null) === 'address')
                && (($parameter['abi_indices'] ?? null) === [0]) && (count($abi['parameters'] ?? []) === 1);
        }
        elseif ($kind === lifecycle_operation_kind::copy_assign) {
            $valid = ($valid) && self::assignment_contract($type, $operation ?? []);
        }
        else
        {
            $result = $operation['result'] ?? [];
            $moving = $kind === lifecycle_operation_kind::move_construct;
            $trait = $moving ? 'move_constructible' : 'copy_constructible';
            $valid = ($valid) && (($type['cpp_traits'][$trait] ?? null) === true)
                && (($operation['storage_precondition'] ?? null) === 'aligned_uninitialized_storage')
                && (($operation['storage_after'] ?? null) === 'live_owned_object')
                && (($operation['source_precondition'] ?? null) === 'live_object')
                && (($operation['source_after'] ?? null) === 'live_object')
                && (($parameter['ownership'] ?? null) === 'borrowed') && (($parameter['passing'] ?? null) === ($moving ? 'mutable_address' : 'const_address'))
                && (($parameter['borrow_scope'] ?? null) === 'call') && (($parameter['abi_indices'] ?? null) === [1])
                && (($result['type'] ?? null) === $type['id']) && (($result['ownership'] ?? null) === 'owned')
                && (($result['passing'] ?? null) === 'caller_storage') && (($result['abi_index'] ?? null) === 0)
                && (count($abi['parameters'] ?? []) === 2);
        }
        if (!$valid)
        {
            throw new \RuntimeException('Unsupported runtime ' . match ($kind) {
                lifecycle_operation_kind::destroy => 'destruction',
                lifecycle_operation_kind::move_construct => 'expiring-source construction',
                lifecycle_operation_kind::copy_assign => 'copy assignment',
                default => 'copy construction',
            } . ' contract');
        }

        // Only validated ordinary pointer positions and exact operation names cross the adapter boundary.
        foreach ($abi['parameters'] as $position) {
            self::address_abi($position);
        }
        $link = $operation['symbol'] ?? null;
        if (!is_string($link) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $link)) {
            throw new \RuntimeException('Invalid runtime lifecycle symbol');
        }
        return new runtime_lifecycle_operation($provider, $operation['id'], $link, 'ccc', $kind);
    }

    /** Both same-type operands stay live; mutable destination and const source may identify the same object. */
    private static function assignment_contract(array $type, array $operation): bool
    {
        if ((($type['cpp_traits']['copy_assignable'] ?? null) !== true)
            || (($operation['storage_precondition'] ?? null) !== 'live_object')
            || (($operation['storage_after'] ?? null) !== 'live_object')
            || (($operation['source_precondition'] ?? null) !== 'live_object')
            || (($operation['source_after'] ?? null) !== 'live_object')
            || (($operation['self_assignment'] ?? null) !== 'native_call')
            || !array_key_exists('result', $operation) || ($operation['result'] !== null)
            || (count($operation['abi']['parameters'] ?? []) !== 2)) {
            return false;
        }
        foreach (['mutable_address', 'const_address'] as $index => $passing)
        {
            $parameter = $operation['parameters'][$index] ?? [];
            if ((($parameter['type'] ?? null) !== $type['id']) || (($parameter['ownership'] ?? null) !== 'borrowed')
                || (($parameter['passing'] ?? null) !== $passing) || (($parameter['borrow_scope'] ?? null) !== 'call')
                || (($parameter['abi_indices'] ?? null) !== [$index])) {
                return false;
            }
        }
        return true;
    }
}
