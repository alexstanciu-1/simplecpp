<?php
declare(strict_types=1);

/* Exact source-debug schema export; no object reflection or JSON roundtrip state. */
namespace read_sources;
// <scpp-imports>
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

final class Source_Json
{
    private static function hex4(int $value): string {
        $digits = '0123456789abcdef';
        return string_byte_slice($digits, (int)($value / 4096) % 16, 1)
            . string_byte_slice($digits, (int)($value / 256) % 16, 1)
            . string_byte_slice($digits, (int)($value / 16) % 16, 1)
            . string_byte_slice($digits, $value % 16, 1);
    }

    /** Preserve JSON_THROW_ON_ERROR's default string spelling for this schema. */
    public static function quote(string $value): string {
        if (!string_utf8_is_valid($value)) {
            throw new \JsonException('Malformed UTF-8 characters, possibly incorrectly encoded', 5);
        }
        $out = '"';
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $code = string_codepoint_at($value, $i);
            if ($code === 34) { $out .= '\"'; }
            elseif ($code === 92) { $out .= '\\\\'; }
            elseif ($code === 47) { $out .= '\/'; }
            elseif ($code === 8) { $out .= '\b'; }
            elseif ($code === 9) { $out .= '\t'; }
            elseif ($code === 10) { $out .= '\n'; }
            elseif ($code === 12) { $out .= '\f'; }
            elseif ($code === 13) { $out .= '\r'; }
            elseif (($code < 32) || ($code >= 128)) {
                if ($code > 65535) {
                    $scalar = $code - 65536;
                    $out .= '\u' . Source_Json::hex4(55296 + (int)($scalar / 1024));
                    $out .= '\u' . Source_Json::hex4(56320 + $scalar % 1024);
                } else { $out .= '\u' . Source_Json::hex4($code); }
            } else { $out .= substr($value, $i, 1); }
        }
        return $out . '"';
    }

    public static function change_name(file_change $change): string {
        if ($change === file_change::unchanged) { return 'unchanged'; }
        if ($change === file_change::added) { return 'added'; }
        if ($change === file_change::changed) { return 'changed'; }
        if ($change === file_change::deleted) { return 'deleted'; }
        return 'moved';
    }

}
