<?php
declare(strict_types=1);

namespace runtime_preparation;
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

/** Readable, reversible symbol spelling from complete identity components. */
final class Symbols
{
    /**
     * Encode the complete ordered identity as one reversible LLVM/C link name.
     * @param list<string> $components
     */
    public static function name(array $components /** vector<string> */): string
    {
        if (count($components) === 0) {
            throw new \InvalidArgumentException('Symbol identity requires an ordered component list');
        }
        sequence_require_strings($components, 'Symbol identity requires an ordered component list',
            'Symbol identity components must be strings');
        $encoded /** string */ = 'rp_';
        $separator /** string */ = '';
        foreach ($components as $component) {
            $encoded = $encoded . $separator . Symbols::component($component);
            $separator = '_X_';
        }
        return $encoded;
    }

    /** Append a fact name to an already encoded type identity. */
    public static function append(string $prefix, string $component): string
    {
        return $prefix . '_X_' . Symbols::component($component);
    }

    private static function component(string $value): string
    {
        $encoded /** string */ = '';
        $hex /** string */ = '0123456789ABCDEF';
        for ($i /** int */ = 0; $i < string_byte_len($value); ++$i) {
            $byte /** int */ = string_byte_at($value, $i);
            if ((($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123)) || (($byte >= 48) && ($byte < 58))) {
                $encoded = $encoded . string_byte_slice($value, $i, 1);
            } else if ($byte === 95) { $encoded = $encoded . '__'; }
            else {
                $encoded = $encoded . '_x' . string_byte_slice($hex, (int)($byte / 16), 1)
                    . string_byte_slice($hex, $byte % 16, 1) . '_';
            }
        }
        return $encoded;
    }
}
