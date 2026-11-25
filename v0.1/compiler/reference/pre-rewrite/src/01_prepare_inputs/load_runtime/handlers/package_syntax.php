<?php
declare(strict_types=1);

/*
 * Role: Private package syntax processing for Package_Adapter.
 * Used by: Package_Adapter; methods execute on that single adapter owner
 * Call map: Package_Adapter::address_abi(); integer_abi(); language_name(); rows()
 * Output: validated compiler contracts; no canonical type IDs allocated here.
 */

namespace load_runtime;

use type_model\abi_extension;
use type_model\runtime_integer_abi;

trait Package_Syntax
{
    /** Accept only the ordinary pointer ABI and attributes supported by the bridge address contract. */
    private static function address_abi(array $position): void
    {
        // No inferred byval/sret/noalias promises. This bridge uses an ordinary address.
        if (($position['type'] ?? null) !== 'ptr') {
            throw new \RuntimeException('Runtime address ABI requires a default-address-space pointer');
        }
        if (!is_string($position['attributes'] ?? null)
            || !in_array(trim($position['attributes']), ['', 'noundef'], true)) {
            throw new \RuntimeException('Unsupported runtime address attributes');
        }
    }

    /** Match the physical integer width to its semantic type and normalize supported extension attributes. */
    private static function integer_abi(mixed $spelling, mixed $attributes, runtime_type $type): runtime_integer_abi
    {
        if (($type->integer_bits === null) || ($spelling !== 'i' . $type->integer_bits) || !is_string($attributes)) {
            throw new \RuntimeException('Runtime integer ABI does not match its semantic type');
        }

        // noundef changes no passing mode; retain at most one supported extension attribute.
        $extension = abi_extension::none;
        foreach (preg_split('/\s+/', trim($attributes), -1, PREG_SPLIT_NO_EMPTY) as $attribute)
        {
            if ($attribute === 'noundef') {
                continue;
            }
            $candidate = abi_extension::tryFrom($attribute);
            if (($candidate === null) || ($extension !== abi_extension::none)) {
                throw new \RuntimeException('Unsupported runtime ABI attribute: ' . $attribute);
            }
            $extension = $candidate;
        }
        return new runtime_integer_abi($type->integer_bits, $extension);
    }

    private static function language_name(array $value): array
    {
        $name = $value['name'] ?? null;
        if (!is_string($name) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name) || (($value['namespace'] ?? null) !== '')) {
            throw new \RuntimeException('Unsupported runtime language name');
        }
        return [$name, ''];
    }

    private static function identifier(mixed $value): string
    {
        if (!is_string($value) || ($value === '')) {
            throw new \RuntimeException('Missing runtime identity');
        }
        return $value;
    }

    private static function positive(mixed $value, string $description): int
    {
        if (!is_int($value) || ($value < 1)) {
            throw new \RuntimeException('Invalid runtime ' . $description);
        }
        return $value;
    }

    /** Require a list of metadata records before callers access individual fields. */
    private static function rows(mixed $value, string $description): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new \RuntimeException('Expected runtime ' . $description . ' list');
        }
        foreach ($value as $row) {
            if (!is_array($row)) {
                throw new \RuntimeException('Expected runtime ' . $description . ' record');
            }
        }
        return $value;
    }
}
