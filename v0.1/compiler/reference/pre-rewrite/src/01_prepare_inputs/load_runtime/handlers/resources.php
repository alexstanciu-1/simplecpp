<?php
declare(strict_types=1);

/*
 * Role: Normalize resource type permissions and call effects at the package boundary.
 * Used by: Package_Adapter; methods execute on that single adapter owner
 * Call map: resource_type(); call_allocation_effect() after physical ABI validation
 * Output: compact type-level obligation and semantic parameter effects, not JSON in consumers.
 */
namespace load_runtime;

trait Resource_Import
{
    /** Resource ownership never follows from an opaque layout or C++ copy/destruction traits. */
    private static function resource_type(array $row): ?\type_model\resource_kind
    {
        if (!array_key_exists('resource', $row)) {
            return null;
        }
        if (($row['resource'] !== 'allocation') || ($row['kind'] !== 'runtime_value')
            || isset($row['lifecycle']['copy_construct']) || isset($row['lifecycle']['copy_assign'])) {
            throw new \RuntimeException('Unsupported allocation resource type');
        }
        return \type_model\resource_kind::allocation;
    }

    /** Validate complete semantic owner positions against normalized mutable/const physical borrows. */
    private static function call_allocation_effect(array $row, array $types): ?\type_model\allocation_effect
    {
        $owners = [];
        foreach ($row['parameters'] as $index => $parameter) {
            if (($types[$parameter['type']]->language_type?->resource ?? null) !== null) {
                $owners[$index] = $parameter;
            }
        }
        if (($types[$row['result']['type']]->language_type?->resource ?? null) !== null) {
            if (($row['kind'] !== 'construct') || ($row['parameters'] !== [])) {
                throw new \RuntimeException('Allocation result requires empty zero-argument construction');
            }
        }
        if (!array_key_exists('allocation_effect', $row)) {
            if ($owners !== []) {
                throw new \RuntimeException('Resource parameters require an allocation effect');
            }
            return null;
        }
        $raw = $row['allocation_effect'];
        if (!is_array($raw) || !is_string($raw['kind'] ?? null)
            || !in_array($row['kind'], ['free_function', 'const_method'], true)) {
            throw new \RuntimeException('Invalid allocation effect');
        }
        $kind = in_array($raw['kind'], ['acquire', 'release', 'transfer', 'inspect'], true)
            ? \type_model\allocation_effect_kind::tryFrom($raw['kind']) : null;
        $expected = ['kind', 'owner', ...($kind === \type_model\allocation_effect_kind::transfer ? ['destination'] : [])];
        if (($kind === null) || (array_diff(array_keys($raw), $expected) !== []) || (array_diff($expected, array_keys($raw)) !== [])
            || !is_int($raw['owner']) || !isset($owners[$raw['owner']])) {
            throw new \RuntimeException('Invalid allocation effect positions');
        }
        $positions = [$raw['owner']];
        if ($kind === \type_model\allocation_effect_kind::transfer) {
            if (!is_int($raw['destination']) || !isset($owners[$raw['destination']]) || ($raw['owner'] === $raw['destination'])
                || ($owners[$raw['owner']]['type'] !== $owners[$raw['destination']]['type'])) {
                throw new \RuntimeException('Allocation transfer requires distinct same-type positions');
            }
            $positions[] = $raw['destination'];
        }
        sort($positions);
        if ($positions !== array_keys($owners)) {
            throw new \RuntimeException('Allocation effect must account for every resource parameter');
        }
        foreach ($owners as $parameter) {
            if ($parameter['passing'] !== ($kind === \type_model\allocation_effect_kind::inspect ? 'const_address' : 'mutable_address')) {
                throw new \RuntimeException('Allocation effect requires compatible borrowed access');
            }
        }
        return new \type_model\allocation_effect($kind, $raw['owner'], $raw['destination'] ?? null);
    }
}
