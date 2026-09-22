<?php
declare(strict_types=1);
namespace read_sources;
// <scpp-imports>
use function scpp\fs_is_windows as fs_is_windows;
use function scpp\fs_basename as fs_basename;
use function scpp\fs_dirname as fs_dirname;
use function scpp\fs_read_text as fs_read_text;
use function scpp\fs_require_realpath as fs_require_realpath;
use function scpp\json_read as json_read;
use function scpp\sequence_require_strings as sequence_require_strings;
use function scpp\fs_is_link as fs_is_link;
use function scpp\fs_is_dir as fs_is_dir;
use function scpp\fs_is_file as fs_is_file;
use function scpp\fs_size as fs_size;
use function scpp\fs_mtime as fs_mtime;
use function scpp\fs_scan as fs_scan;
use function scpp\json_quote as json_quote;
use function scpp\string_byte_from_int as string_byte_from_int;
use function scpp\enum_name as enum_name;
use function scpp\lock_empty as lock_empty;
use function scpp\lock_try as lock_try;
use function scpp\lock_release as lock_release;
use function scpp\lock_transfer as lock_transfer;
use function scpp\process_spawn as process_spawn;
use function scpp\process_poll as process_poll;
use function scpp\process_output as process_output;
use function scpp\process_stop as process_stop;
use function scpp\process_close as process_close;
use function scpp\sequence_map as sequence_map;
use function scpp\sequence_filter as sequence_filter;
use function scpp\keyed_map as keyed_map;
use function scpp\keyed_filter as keyed_filter;
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>

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
