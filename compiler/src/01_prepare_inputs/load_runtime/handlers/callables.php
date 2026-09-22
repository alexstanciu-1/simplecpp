<?php
declare(strict_types=1);

/*
 * Role: Private callables processing for Package_Adapter.
 * Used by: Package_Adapter; methods execute on that single adapter owner
 * Call map: Package_Adapter::callables() -> call_result(); call_parameter(); Callable_Bindings::validate()
 * Output: validated compiler contracts; no canonical type IDs allocated here.
 */

namespace load_runtime;

use type_model\abi_extension;
use type_model\named_type_reference;
use type_model\result_passing;
use type_model\runtime_borrow_abi;
use type_model\runtime_byte_span_abi;
use type_model\runtime_callable;
use type_model\runtime_integer_abi;

trait Callable_Import
{
    /** Translate source or compiler-bound operations into semantic/ABI contracts; validate all operation identities. */
    private static function callables(array $rows, array $types, string $provider, ?package_bindings $bindings = null): array
    {
        $callables = [];
        $ids = [];
        $names = [];
        $links = [];
        foreach ($rows as $row)
        {
            // Reserve identities for every operation, including implicit lifecycle helpers.
            $id = self::identifier($row['id'] ?? null);
            $link = $row['symbol'] ?? null;
            if (isset($ids[$id]) || !is_string($link) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $link) || isset($links[$link])) {
                throw new \RuntimeException('Invalid or duplicate runtime operation identity');
            }
            $ids[$id] = true;
            $links[$link] = true;
            $exposure = $bindings?->callables[$id] ?? null;
            if (($exposure !== null) && !($exposure instanceof named_type_reference)) {
                throw new \RuntimeException('Invalid compiler runtime callable binding');
            }
            if (!isset($row['expose_as']) && ($exposure === null)) {
                continue;
            }

            // Source exposure requires a supported operation and a bridge-contained error policy.
            [$name, $namespace] = $exposure === null ? self::language_name($row['expose_as'])
                : [$exposure->name, $exposure->namespace_name];
            if (isset($names[$name]) || !in_array($row['kind'] ?? null, ['free_function', 'construct', 'construct_from_bytes', 'const_method'], true)
                || (($row['calling_convention'] ?? null) !== 'ccc') || (($row['error_policy'] ?? null) !== 'terminate')
                || (($row['exception_boundary'] ?? null) !== 'caught_in_bridge')) {
                throw new \RuntimeException('Unsupported or duplicate exposed runtime callable: ' . $name);
            }
            $names[$name] = true;

            // Resolve the semantic result before mapping its physical return or hidden destination.
            $parameters = [];
            $parameter_abi = [];
            $abi = $row['abi'] ?? [];
            $positions = self::rows($abi['parameters'] ?? null, 'ABI parameters');
            [$result_type, $mode, $return_abi, $offset] = self::call_result($row, $types, $positions);

            if (isset($bindings?->sources[$row['result']['type'] ?? ''])
                && (($row['result']['payload_crossing'] ?? null) !== 'copy_out')) {
                throw new \RuntimeException('Source result requires explicit copy-out');
            }

            // Validate semantic parameters and retain only their exact names; record shapes resolve later.
            // The physical cursor accounts for the hidden construction destination.
            foreach (self::rows($row['parameters'] ?? null, 'parameters') as $parameter)
            {
                if (isset($bindings?->sources[$parameter['type'] ?? ''])
                    && ((($parameter['payload_crossing'] ?? null) !== 'copy_in') || (($parameter['passing'] ?? null) !== 'const_address'))) {
                    throw new \RuntimeException('Source parameter requires explicit const copy-in');
                }
                [$definition, $passing] = self::call_parameter($parameter, $types, $positions, $offset);
                $semantic_passing = match ($parameter['passing']) {
                    'direct' => \type_model\argument_passing::value,
                    'byte_span' => \type_model\argument_passing::byte_span,
                    'const_address' => \type_model\argument_passing::borrow_const,
                    'mutable_address' => \type_model\argument_passing::borrow_mutable,
                };
                $parameters[] = new \type_model\semantic_parameter(
                    new named_type_reference($definition->name, $definition->namespace_name), $semantic_passing);
                $parameter_abi[] = $passing;
            }

            // Reject surplus physical arguments before publishing the normalized callable.
            if (count($positions) !== $offset) {
                throw new \RuntimeException('Runtime semantic/ABI parameter count mismatch');
            }
            $production = ($row['result']['ownership'] === 'owned') ? \type_model\result_production::owned
                : ($result_type->representation->kind === \type_model\representation_kind::void_type
                    ? \type_model\result_production::none : \type_model\result_production::value);
            $signature = new \type_model\semantic_signature($parameters, new \type_model\semantic_result(
                new named_type_reference($result_type->name, $result_type->namespace_name), $production),
                self::call_allocation_effect($row, $types));
            $physical = new \type_model\runtime_callable_abi($link, 'ccc', $return_abi, $parameter_abi, $mode);
            [$binding, $default_literal] = self::call_language_binding($row, $signature);
            $callables[] = new runtime_callable($provider, $id, $name, $namespace, $signature, $physical,
                $binding, $default_literal, self::call_conversion($row, $signature));
        }

        foreach ($bindings?->callables ?? [] as $id => $binding) {
            if (!isset($ids[$id]) || !($binding instanceof named_type_reference)) {
                throw new \RuntimeException('Unknown or invalid compiler runtime callable binding');
            }
        }
        Callable_Bindings::validate($callables);
        return $callables;
    }

    /** Validate a semantic result and consume its optional hidden destination position. */
    private static function call_result(array $row, array $types, array $positions): array
    {
        $abi = $row['abi'];
        $result = $row['result'] ?? [];
        $type = $types[$result['type'] ?? ''] ?? null;
        $mode = result_passing::tryFrom($result['passing'] ?? '');
        if (($type?->language_type === null) || ($mode === null)) {
            throw new \RuntimeException('Unsupported runtime result passing or language type');
        }

        $offset = 0;
        $return_abi = null;

        // Construction uses the first ABI argument as caller-owned, uninitialized storage.
        if ($mode === result_passing::caller_storage)
        {
            if (!in_array($row['kind'], ['construct', 'construct_from_bytes', 'free_function'], true) || !in_array($type->storage->kind, [runtime_storage_kind::opaque_inline, runtime_storage_kind::record], true)
                || (($result['ownership'] ?? null) !== 'owned') || (($result['abi_index'] ?? null) !== 0)
                || (($row['storage_precondition'] ?? null) !== 'aligned_uninitialized_storage')
                || (($abi['return_type'] ?? null) !== 'void') || (($abi['return_attributes'] ?? null) !== '')) {
                throw new \RuntimeException('Unsupported runtime caller-storage result');
            }
            if (($row['kind'] === 'free_function') && (($row['storage_after'] ?? null) !== 'live_owned_object')) {
                throw new \RuntimeException('Owned function result requires a live-object postcondition');
            }
            self::address_abi($positions[0] ?? []);
            $offset = 1;
        }
        // Direct results are either void or scalar values with a measured integer extension policy.
        else
        {
            if (($result['ownership'] ?? null) !== 'value') {
                throw new \RuntimeException('Unsupported runtime result ownership');
            }
            if ($type->storage->kind === runtime_storage_kind::void_type) {
                if (($abi['return_type'] !== 'void') || ($abi['return_attributes'] !== '')) {
                    throw new \RuntimeException('Invalid void result ABI');
                }
            }
            else {
                $return_abi = self::integer_abi($abi['return_type'] ?? null, $abi['return_attributes'] ?? null, $type);
            }
        }
        return [$type->language_type, $mode, $return_abi, $offset];
    }

    /** Normalize one semantic parameter, advancing the physical cursor by its declared ABI expansion. */
    private static function call_parameter(array $parameter, array $types, array $positions, int &$offset): array
    {
        $type = $types[$parameter['type'] ?? ''] ?? null;
        $definition = $type?->language_type ?? $type?->record;
        $indices = ($parameter['passing'] ?? null) === 'byte_span' ? [$offset, $offset + 1] : [$offset];
        if (($definition === null) || (($parameter['abi_indices'] ?? null) !== $indices)) {
            throw new \RuntimeException('Unsupported runtime parameter passing or language type');
        }
        $position = $positions[$offset] ?? [];

        if (($parameter['passing'] ?? null) === 'byte_span')
        {
            if (($type->storage->kind !== runtime_storage_kind::byte_span)
                || (($parameter['ownership'] ?? null) !== 'borrowed') || (($parameter['borrow_scope'] ?? null) !== 'call')
                || (($parameter['length_signed'] ?? null) !== false)) {
                throw new \RuntimeException('Unsupported byte-span contract');
            }
            self::address_abi($position);
            $length = $positions[$offset + 1] ?? [];
            if (!preg_match('/^i([1-9][0-9]*)$/D', $length['type'] ?? '', $width)
                || (($parameter['length_abi_type'] ?? null) !== ($length['type'] ?? null))
                || !in_array($length['attributes'] ?? null, ['', 'noundef'], true)) {
                throw new \RuntimeException('Unsupported byte-span length ABI');
            }
            $parameter_abi = new runtime_byte_span_abi(new runtime_integer_abi((int)$width[1], abi_extension::none));
            $offset += 2;
            return [$definition, $parameter_abi];
        }

        // Objects and const scalars expose a call-scoped address; ownership stays with the caller.
        if (in_array($parameter['passing'] ?? null, ['const_address', 'mutable_address'], true)
            && (($parameter['ownership'] ?? null) === 'borrowed')
            && (($parameter['borrow_scope'] ?? null) === 'call')
            && (in_array($type->storage->kind, [runtime_storage_kind::opaque_inline, runtime_storage_kind::record], true)
                || (($type->storage->kind === runtime_storage_kind::integer) && ($parameter['passing'] === 'const_address')))) {
            self::address_abi($position);
            $parameter_abi = new runtime_borrow_abi($parameter['passing'] === 'mutable_address');
        }
        // Scalar arguments pass their value using the measured integer ABI.
        elseif ((($parameter['passing'] ?? null) === 'direct') && (($parameter['ownership'] ?? null) === 'value')) {
            $parameter_abi = self::integer_abi($position['type'] ?? null, $position['attributes'] ?? null, $type);
        }
        else {
            throw new \RuntimeException('Unsupported runtime parameter passing or language type');
        }
        ++$offset;
        return [$definition, $parameter_abi];
    }
}
