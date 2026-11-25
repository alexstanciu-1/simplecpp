<?php
declare(strict_types=1);
namespace instantiate;

/** One bounded policy snapshot, selected by the host before concrete preparation. */
final class Instantiation_Policy {
    /** The host supplies the compiler data directory; source __DIR__ is not a native install path. */
    public static function input_path(string $language_directory): string {
        return $language_directory . '/instantiation_limits.json';
    }
    public static function load(string $path): int {
        $content = '';
        try { $content = fs_read_text($path); }
        catch (\RuntimeException $error) { throw new \RuntimeException('Cannot read instantiation policy: ' . $path); }
        return Instantiation_Policy::parse($path, $content);
    }
    public static function parse(string $path, string $content): int {
        $policy = json_read($content);
        $limit = 0;
        if ($policy->kind() !== 'object') { throw new \RuntimeException('Invalid instantiation limit: ' . $path); }
        if (!$policy->has('max_instances')) { throw new \RuntimeException('Invalid instantiation limit: ' . $path); }
        try { $limit = $policy->member('max_instances')->integer(); }
        catch (\RuntimeException $error) { throw new \RuntimeException('Invalid instantiation limit: ' . $path); }
        if (($limit < 1) || ($limit > \collect_symbols\MAX_SYMBOL_ID)) {
            throw new \RuntimeException('Invalid instantiation limit: ' . $path);
        }
        return $limit;
    }
}
