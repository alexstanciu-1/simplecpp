<?php
declare(strict_types=1);

/*
 * Role: Import storage families with complete, target-verified native primitive roles.
 * Used by: Package_Adapter::read_package(); private methods on that owner
 * Call map: storage_families() -> storage_primitive()
 * Output: shared semantic contracts; no source element types or layouts are invented.
 */
namespace load_runtime;

trait Storage_Import
{
    /** Require the complete supported protocol and exact primitive signatures before exposing a family. */
    private static function storage_families(array $rows, array $types, array $operations, string $provider): array
    {
        $by_id = array_column($operations, null, 'id');
        $families = [];
        foreach ($rows as $row)
        {
            if (!array_key_exists('storage_family', $row)) {
                continue;
            }
            $raw = $row['storage_family'];
            if (!is_array($raw) || (count($raw) !== 3)
                || !is_string($raw['counter_type'] ?? null)
                || !is_array($raw['primitives'] ?? null) || !is_array($raw['operations'] ?? null)
                || (($row['kind'] ?? null) !== 'runtime_value') || isset($row['resource'])) {
                throw new \RuntimeException('Unsupported typed storage family contract');
            }
            $descriptor = $types[$row['id']]->language_type;
            $counter = $types[$raw['counter_type'] ?? ''] ?? null;
            $void = null;
            foreach ($types as $type) {
                if ($type->storage->kind === runtime_storage_kind::void_type) {
                    $void ??= $type->language_type;
                }
            }
            if (($descriptor?->representation->kind !== \type_model\representation_kind::opaque_inline)
                || ($descriptor->lifetime->copy !== \type_model\copy_kind::unavailable)
                || ($descriptor->lifetime->cleanup !== \type_model\cleanup_kind::none)
                || ($descriptor->lifetime->default_constructor === null)
                || ($counter?->language_type === null) || ($counter->signed !== true) || ($void === null)) {
                throw new \RuntimeException('Unsupported typed storage family contract');
            }
            $schemas = ['allocate' => ['void', false, 3], 'next' => ['address', false, 0],
                'commit' => ['void', false, 0], 'at' => ['address', true, 1],
                'pop' => ['void', false, 0], 'count' => ['integer', true, 0],
                'release' => ['void', false, 0], 'transfer' => ['void', false, -1]];
            if (array_diff(array_keys($raw['primitives'] ?? []), array_keys($schemas)) !== []) {
                throw new \RuntimeException('Unknown storage primitive role');
            }
            $primitives = [];
            foreach ($schemas as $role => [$result, $const, $integers]) {
                $operation = $by_id[$raw['primitives'][$role] ?? ''] ?? [];
                $primitives[$role] = self::storage_primitive($operation, $row['id'], $raw['counter_type'], $types,
                    $result, $const, $integers);
            }
            $names = $raw['operations'] ?? [];
            $roles = array_map(static fn($role) => $role->value, \type_model\storage_role::cases());
            if ((array_diff($roles, array_keys($names)) !== []) || (array_diff(array_keys($names), $roles) !== [])) {
                throw new \RuntimeException('Storage family requires complete operation names');
            }
            foreach ($names as $name) {
                self::language_name(['name' => $name, 'namespace' => $descriptor->namespace_name]);
            }
            if (count(array_unique($names)) !== count($names)) {
                throw new \RuntimeException('Duplicate storage operation name');
            }
            $families[] = new \type_model\storage_family($provider, $row['id'], $descriptor, $counter->language_type,
                $void, $primitives, $names, $descriptor->name, $descriptor->namespace_name);
        }
        return $families;
    }

    /** Native primitives are internal calls; validate semantic parameters as well as measured pointer/integer ABI. */
    private static function storage_primitive(array $row, string $owner, string $counter, array $types,
        string $result_kind, bool $const, int $integers): \type_model\storage_primitive
    {
        $semantic = $row['parameters'] ?? [];
        $abi = $row['abi'] ?? [];
        $expected = [$owner, ...($integers < 0 ? [$owner] : array_fill(0, $integers, $counter))];
        $physical = $abi['parameters'] ?? [];
        if ((($row['kind'] ?? null) !== 'free_function') || isset($row['expose_as'])
            || isset($row['allocation_effect']) || isset($row['conversion_purpose'])
            || (($row['calling_convention'] ?? null) !== 'ccc') || (($row['error_policy'] ?? null) !== 'terminate')
            || (($row['exception_boundary'] ?? null) !== 'caught_in_bridge')
            || (count($semantic) !== count($expected)) || (count($physical) !== count($expected))
            || (($row['result']['passing'] ?? null) !== 'direct') || (($row['result']['ownership'] ?? null) !== 'value')) {
            throw new \RuntimeException('Unsupported storage primitive signature');
        }
        $parameters = [];
        foreach ($expected as $index => $type)
        {
            $parameter = $semantic[$index];
            $borrow = $type === $owner;
            if (($parameter['type'] !== $type) || ($parameter['abi_indices'] !== [$index])
                || ($parameter['passing'] !== ($borrow ? ($const ? 'const_address' : 'mutable_address') : 'direct'))
                || ($parameter['ownership'] !== ($borrow ? 'borrowed' : 'value'))
                || (($borrow) && (($parameter['borrow_scope'] ?? null) !== 'call'))) {
                throw new \RuntimeException('Storage primitive has incompatible semantic parameters');
            }
            if ($borrow) {
                self::address_abi($physical[$index]);
                $parameters[] = new \type_model\runtime_borrow_abi(!$const);
            }
            else {
                $parameters[] = self::integer_abi($physical[$index]['type'], $physical[$index]['attributes'], $types[$counter]);
            }
        }
        $result = $types[$row['result']['type'] ?? ''] ?? null;
        if ($result?->storage->kind->value !== $result_kind) {
            throw new \RuntimeException('Storage primitive result kind mismatch');
        }
        $result_abi = null;
        if ($result_kind === 'address') {
            self::address_abi(['type' => $abi['return_type'], 'attributes' => $abi['return_attributes']]);
            $result_abi = new \type_model\runtime_borrow_abi();
        }
        elseif ($result_kind === 'integer') {
            if ($result !== $types[$counter]) {
                throw new \RuntimeException('Storage count result differs from its counter type');
            }
            $result_abi = self::integer_abi($abi['return_type'], $abi['return_attributes'], $result);
        }
        elseif (($abi['return_type'] !== 'void') || ($abi['return_attributes'] !== '')) {
            throw new \RuntimeException('Storage primitive requires physical void result');
        }
        $link = $row['symbol'] ?? '';
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $link)) {
            throw new \RuntimeException('Invalid storage primitive link name');
        }
        return new \type_model\storage_primitive($link, $result_abi, $parameters);
    }
}
