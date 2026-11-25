<?php
declare(strict_types=1);

/* Decode non-interpolated quoted bytes; runtime constructor selection belongs to body checking. */
namespace check_bodies;
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

final class Byte_Literals
{
    private static function hex_digit(int $byte): int
    {
        if (($byte >= 48) && ($byte < 58)) { return $byte - 48; }
        if (($byte >= 65) && ($byte < 71)) { return $byte - 55; }
        if (($byte >= 97) && ($byte < 103)) { return $byte - 87; }
        return -1;
    }

    public static function decode(string $text): string
    {
        $quote /** int */ = string_byte_at($text, 0);
        $end /** int */ = string_byte_len($text) - 1;
        if ((($quote !== 39) && ($quote !== 34)) || ($end < 1) || (string_byte_at($text, $end) !== $quote)) {
            throw new \InvalidArgumentException('Malformed quoted literal');
        }
        $bytes /** string */ = '';
        for ($index /** int */ = 1; $index < $end; ++$index) {
            $byte /** int */ = string_byte_at($text, $index);
            $following /** int */ = string_byte_at($text, $index + 1);
            if (($quote === 34) && ($byte === 36) &&
                ((($following >= 65) && ($following < 91)) || (($following >= 97) && ($following < 123)) ||
                 ($following === 95) || ($following === 123) || ($following >= 128))) {
                throw new \InvalidArgumentException('String interpolation is not supported');
            }
            if ($byte !== 92) {
                $bytes = $bytes . string_byte_slice($text, $index, 1);
                continue;
            }
            ++$index;
            $next /** int */ = string_byte_at($text, $index);
            if (($next === 92) || ($next === $quote)) {
                $bytes = $bytes . string_byte_slice($text, $index, 1);
                continue;
            }
            if ($quote === 39) {
                $bytes = $bytes . "\\" . string_byte_slice($text, $index, 1);
                continue;
            }
            $value /** int */ = -1;
            if ($next === 110) { $value = 10; }
            else if ($next === 114) { $value = 13; }
            else if ($next === 116) { $value = 9; }
            else if ($next === 118) { $value = 11; }
            else if ($next === 102) { $value = 12; }
            else if ($next === 101) { $value = 27; }
            else if ($next === 36) { $value = 36; }
            else if (($next >= 48) && ($next < 56)) {
                $value = $next - 48;
                for ($digits /** int */ = 1; ($digits < 3) && ($index + 1 < $end); ++$digits) {
                    $digit /** int */ = string_byte_at($text, $index + 1) - 48;
                    if (($digit < 0) || ($digit > 7)) { break; }
                    $value = $value * 8 + $digit;
                    ++$index;
                }
                $value = $value % 256;
            }
            else if ($next === 120) {
                for ($digits /** int */ = 0; ($digits < 2) && ($index + 1 < $end); ++$digits) {
                    $digit /** int */ = Byte_Literals::hex_digit(string_byte_at($text, $index + 1));
                    if ($digit < 0) { break; }
                    if ($value < 0) { $value = 0; }
                    $value = $value * 16 + $digit;
                    ++$index;
                }
            }
            else if (($next === 117) && (string_byte_at($text, $index + 1) === 123)) {
                throw new \InvalidArgumentException('Unicode escape syntax is not supported; use UTF-8 literal bytes');
            }
            if ($value >= 0) { $bytes = $bytes . string_byte_from_int($value); }
            else { $bytes = $bytes . "\\" . string_byte_slice($text, $index, 1); }
        }
        return $bytes;
    }
}
