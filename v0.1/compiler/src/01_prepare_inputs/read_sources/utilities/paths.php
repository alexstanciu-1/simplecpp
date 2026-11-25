<?php
declare(strict_types=1);
namespace read_sources;

final class Source_Paths {
    public static function join(string $base, string $relative): string {
        if (string_byte_ends_with($base, '/')) { return $base . $relative; }
        return $base . '/' . $relative;
    }
    public static function is_absolute(string $path, bool $windows): bool {
        if (string_byte_starts_with($path, '/')) { return true; }
        if (!$windows) { return false; }
        if (string_byte_starts_with($path, '\\')) { return true; }
        if (string_byte_len($path) < 3) { return false; }
        if (string_byte_at($path, 1) !== 58) { return false; }
        $separator = string_byte_at($path, 2);
        return ($separator === 47) || ($separator === 92);
    }
    public static function normalize(string $path, bool $windows): string {
        if (!$windows) { return $path; }
        $out = '';
        for ($i /** int */ = 0; $i < string_byte_len($path); ++$i) {
            $byte = string_byte_at($path, $i);
            $out = $out . ($byte === 92 ? '/' : string_byte_from_int($byte));
        }
        return $out;
    }
    public static function resolve(string $base, string $path): string {
        $candidate = $path;
        if (!Source_Paths::is_absolute($path, fs_is_windows())) {
            $candidate = Source_Paths::join($base, $path);
        }
        return Source_Paths::normalize(fs_require_realpath($candidate), fs_is_windows());
    }
    public static function overlaps(string $first, string $second): bool {
        if ($first === $second) { return true; }
        return string_byte_starts_with($first, Source_Paths::join($second, ''))
            || string_byte_starts_with($second, Source_Paths::join($first, ''));
    }
    public static function is_source(string $path): bool {
        return string_byte_ends_with($path, '.phs');
    }
}
