<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Validate the typed prefix protocol before native compilation; target ABI validation belongs to import. */
final class Storage_Contracts
{
    /** Every family declares a closed operation protocol; arbitrary native addresses stay hidden. */
    public static function validate(array $types, array $operations): void
    {
        foreach ($types as $type)
        {
            if (!array_key_exists('storage_family', $type)) {
                continue;
            }
            $family = $type['storage_family'];
            if (($type['kind'] !== 'runtime_value') || !is_array($family) || !isset($type['language_type'])
                || (($type['lifecycle']['cleanup'] ?? null) !== 'none') || !isset($type['lifecycle']['default_construct'])
                || isset($type['lifecycle']['copy_construct']) || isset($type['lifecycle']['destroy'])
                || isset($type['resource'])) {
                throw new \RuntimeException('Unsupported typed storage family contract');
            }
            Definitions::fields($family, ['counter_type', 'primitives', 'operations'], 'storage family');
            $counter = $family['counter_type'];
            if (!is_string($counter) || (($types[$counter]['kind'] ?? null) !== 'integer')
                || !isset($types[$counter]['language_type'])) {
                throw new \RuntimeException('Storage family requires a declared integer counter');
            }
            $schemas = ['allocate' => ['void', false, 3], 'next' => ['address', false, 0],
                'commit' => ['void', false, 0], 'at' => ['address', true, 1],
                'pop' => ['void', false, 0], 'count' => ['integer', true, 0],
                'release' => ['void', false, 0], 'transfer' => ['void', false, -1]];
            if (!is_array($family['primitives']) || !is_array($family['operations'])) {
                throw new \RuntimeException('Storage family requires primitive and operation objects');
            }
            Definitions::fields($family['primitives'], array_keys($schemas), 'storage primitives');
            Definitions::fields($family['operations'], ['allocate', 'push', 'pop', 'count', 'release', 'transfer'], 'storage operations');
            foreach ($family['operations'] as $name) {
                Definitions::identifier($name, '/^[A-Za-z_][A-Za-z0-9_]*$/D', 'storage operation name');
            }
            if (count(array_unique($family['operations'])) !== count($family['operations'])) {
                throw new \RuntimeException('Duplicate storage operation name');
            }
            foreach ($schemas as $role => [$result, $const, $integers]) {
                $id = $family['primitives'][$role];
                Definitions::identifier($id, '/^[a-z][a-z0-9_.]*$/D', 'storage primitive id');
                self::primitive($operations[$id] ?? [], $types, $type['id'], $counter, $result, $const, $integers);
            }
        }
    }

    /** Exact role signatures keep prefix publication and raw addresses inaccessible to ordinary source calls. */
    private static function primitive(array $operation, array $types, string $owner, string $counter,
        string $result, bool $const, int $integers): void
    {
        $borrow = ['type' => $owner, 'passing' => $const ? 'const_address' : 'mutable_address', 'borrow_scope' => 'call'];
        $parameters = [$borrow, ...($integers < 0 ? [$borrow] : array_fill(0, $integers, $counter))];
        if ((($operation['kind'] ?? null) !== 'free_function') || isset($operation['expose_as'])
            || isset($operation['allocation_effect']) || isset($operation['conversion_purpose'])
            || (($operation['parameters'] ?? null) != $parameters)
            || (($types[$operation['result_type'] ?? '']['kind'] ?? null) !== $result)
            || (($result === 'integer') && ($operation['result_type'] !== $counter))) {
            throw new \RuntimeException('Unsupported storage primitive signature');
        }
    }
}
