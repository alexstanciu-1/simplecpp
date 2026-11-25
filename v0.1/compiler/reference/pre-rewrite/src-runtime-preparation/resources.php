<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Validate explicit resource effects without guessing from C++ types or operation names. */
final class Resource_Contracts
{
    /** Resource ownership is independent of native object destruction, and has no implicit copy permission. */
    public static function type(array $type): void
    {
        if (!array_key_exists('resource', $type)) {
            return;
        }
        if (($type['resource'] !== 'allocation') || ($type['kind'] !== 'runtime_value')
            || isset($type['lifecycle']['copy_construct']) || isset($type['lifecycle']['copy_assign'])) {
            throw new \RuntimeException('Allocation resource requires a noncopyable runtime value');
        }
    }

    /** Validate every exposed resource operand, result producer and state transition against its declared ABI use. */
    public static function operation(array $operation, array $types): void
    {
        $parameters = $operation['parameters'] ?? [];
        if ($operation['kind'] === 'const_method') {
            $parameters = [['type' => $operation['type'], 'passing' => 'const_address']];
        }
        $owners = [];
        foreach ($parameters as $index => $parameter) {
            $type = is_string($parameter) ? $parameter : $parameter['type'];
            if (isset($types[$type]['resource'])) {
                $owners[$index] = [$type, is_string($parameter) ? 'direct' : $parameter['passing']];
            }
        }
        $result = $types[$operation['result_type'] ?? $operation['type'] ?? ''] ?? [];
        $construct = in_array($operation['kind'], ['construct', 'construct_from_bytes', 'copy_construct'], true);
        if (isset($result['resource']) && ($construct || ($operation['kind'] === 'free_function'))) {
            if (($operation['kind'] !== 'construct') || ($parameters !== [])) {
                throw new \RuntimeException('Allocation owners can only be produced by empty zero-argument construction');
            }
        }
        $effect = $operation['allocation_effect'] ?? null;
        if ($effect === null) {
            if (array_key_exists('allocation_effect', $operation) || ($owners !== [])) {
                throw new \RuntimeException('Resource parameters require an explicit allocation effect');
            }
            return;
        }
        if (!is_array($effect) || !in_array($operation['kind'], ['free_function', 'const_method'], true)) {
            throw new \RuntimeException('Unsupported allocation effect operation');
        }
        $kind = $effect['kind'] ?? '';
        Definitions::fields($effect, ['kind', 'owner', ...($kind === 'transfer' ? ['destination'] : [])], 'allocation effect');
        if (!in_array($kind, ['acquire', 'release', 'transfer', 'inspect'], true) || !is_int($effect['owner'])) {
            throw new \RuntimeException('Invalid allocation effect');
        }
        $owner = $effect['owner'];
        $positions = [$owner];
        if ($kind === 'transfer') {
            $destination = $effect['destination'];
            if (!is_int($destination) || ($destination === $owner) || (($owners[$owner][0] ?? null) !== ($owners[$destination][0] ?? null))) {
                throw new \RuntimeException('Allocation transfer requires distinct same-type owner positions');
            }
            $positions[] = $destination;
        }
        sort($positions);
        if ($positions !== array_keys($owners)) {
            throw new \RuntimeException('Allocation effect must account for every resource parameter');
        }
        foreach ($owners as [, $passing]) {
            if ($passing !== ($kind === 'inspect' ? 'const_address' : 'mutable_address')) {
                throw new \RuntimeException('Allocation effect has incompatible borrowing');
            }
        }
    }
}
