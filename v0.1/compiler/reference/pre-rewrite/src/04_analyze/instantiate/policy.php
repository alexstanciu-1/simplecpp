<?php
declare(strict_types=1);

/*
 * Role: Read the bounded instance-preparation policy before work selection.
 * Used by: Compiler_Session; standalone Concrete_Preparation lifecycle
 * Call map: Instantiation_Policy::load() -> input_path(); [action] validate integer limit
 */
namespace instantiate;

final class Instantiation_Policy
{
    /** Read one policy snapshot; the coordinator includes its value in cache validity. */
    public static function load(?string $path = null): int
    {
        $path = self::input_path($path);
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Cannot read instantiation policy: ' . $path);
        }
        $policy = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $limit = $policy['max_instances'] ?? null;
        if (!is_int($limit) || ($limit <= 0) || ($limit > \collect_symbols\MAX_SYMBOL_ID)) {
            throw new \RuntimeException('Invalid instantiation limit: ' . $path);
        }
        return $limit;
    }

    public static function input_path(?string $path = null): string
    {
        return $path ?? dirname(__DIR__, 3) . '/language/instantiation_limits.json';
    }
}
