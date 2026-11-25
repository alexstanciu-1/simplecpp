<?php
declare(strict_types=1);

/*
 * Role: Explicit project namespace and roots for compiler-owned native exports.
 * Used by: Source_Identities; Source_Export_Preparation
 * Flow: coordinator configuration -> fixed preparation tasks; no filesystem writes.
 */
namespace compile;
// <scpp-imports>
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

/** Portable identity uses project_key; physical roots only locate inputs and future outputs. */
final class native_project
{
    public readonly string $source_root;
    public readonly string $output_root;

    /** Require an explicit namespace and normalized absolute roots; never infer identity from a path. */
    public function __construct(public readonly string $project_key, string $source_root, string $output_root)
    {
        if (($project_key === '') || native_project::has_nul($project_key)) {
            throw new \InvalidArgumentException('Native project requires an explicit project key');
        }
        $this->source_root = native_project::root($source_root);
        $this->output_root = native_project::root($output_root);
    }

    private static function has_nul(string $text): bool
    {
        for ($i /** int */ = 0; $i < string_byte_len($text); ++$i) {
            if (string_byte_at($text, $i) === 0) { return true; }
        }
        return false;
    }

    /** Lexical POSIX roots, preserving arbitrary non-NUL path bytes; no filesystem access. */
    private static function root(string $path): string
    {
        if (!string_byte_starts_with($path, '/') || native_project::has_nul($path)) {
            throw new \InvalidArgumentException('Native project roots must be absolute');
        }
        $out /** string */ = '';
        $start /** int */ = 0;
        $size /** int */ = string_byte_len($path);
        for ($end /** int */ = 0; $end < $size + 1; ++$end) {
            if (($end !== $size) && (string_byte_at($path, $end) !== 47)) { continue; }
            $part /** string */ = string_byte_slice($path, $start, $end - $start);
            $start = $end + 1;
            if (($part === '') || ($part === '.')) { continue; }
            if ($part === '..') {
                throw new \InvalidArgumentException('Native project roots must not contain parent traversal');
            }
            $out = $out . '/' . $part;
        }
        if ($out === '') { return '/'; }
        return $out;
    }
}
