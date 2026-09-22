<?php
declare(strict_types=1);

/*
 * Role: Allocate exact concrete identities within one retained instance registry.
 * Used by: Instance_Join; Member_Join
 * Call map: Instance_Identities::allocate() -> Type_Cache::materialize()
 * Writes: only the accepting join's private key map and allocation watermark.
 */
namespace instantiate;

final class Instance_Identities
{
    /** Preserve allocated IDs across updates; exact ordered typed arguments distinguish specializations. */
    public static function allocate(\type_model\Type_Store $types, int $definition, array $arguments,
        array &$keys, int &$next): int
    {
        $parts = [];
        foreach ($arguments as $argument) {
            $parts[] = [\resolve_types\Type_Cache::materialize($types, $argument->type), $argument->value];
        }
        $key = json_encode([$definition, $parts], JSON_THROW_ON_ERROR);
        if (isset($keys[$key])) {
            return $keys[$key];
        }
        if ($next > \collect_symbols\MAX_SYMBOL_ID) {
            throw new \OverflowException('Template instance identity space exhausted');
        }
        return $keys[$key] = $next++;
    }
}
