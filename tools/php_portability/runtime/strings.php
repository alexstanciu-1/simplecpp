<?php
declare(strict_types=1);

namespace scpp {
function json_quote(string $text): string {
    return \json_encode($text, JSON_THROW_ON_ERROR);
}

function string_byte_from_int(int $value): string {
    if ($value < 0 || $value > 255) { throw new \InvalidArgumentException('Byte value must be between 0 and 255'); }
    return \chr($value);
}

function string_byte_len(string $text): int { return \strlen($text); }
function string_byte_starts_with(string $text, string $prefix): bool { return \str_starts_with($text, $prefix); }
function string_byte_ends_with(string $text, string $suffix): bool { return \str_ends_with($text, $suffix); }
function string_utf8_is_valid(string $text): bool { return \mb_check_encoding($text, 'UTF-8'); }
function require_utf8(string $text): void {
    if (!string_utf8_is_valid($text)) { throw new \InvalidArgumentException('Text operation requires valid UTF-8'); }
}
// Unicode scalar value at a code-point index, or -1 outside the string.
function string_codepoint_at(string $text, int $index): int {
    require_utf8($text);
    if ($index < 0 || $index >= \mb_strlen($text, 'UTF-8')) { return -1; }
    return \mb_ord(\mb_substr($text, $index, 1, 'UTF-8'), 'UTF-8');
}
}

namespace scpp\compat {
function strlen(string $text): int {
    \scpp\require_utf8($text);
    return \mb_strlen($text, 'UTF-8');
}
function substr(string $text, int $offset, int $length = PHP_INT_MAX): string {
    \scpp\require_utf8($text);
    return \mb_substr($text, $offset, $length, 'UTF-8');
}
function strpos(string $text, string $needle, int $offset = 0): int|false {
    \scpp\require_utf8($text);
    \scpp\require_utf8($needle);
    $size = \mb_strlen($text, 'UTF-8');
    if ($offset < -$size || $offset > $size) { throw new \OutOfBoundsException('Text search offset is out of range'); }
    return \mb_strpos($text, $needle, $offset, 'UTF-8');
}
function strrpos(string $text, string $needle, int $offset = 0): int|false {
    \scpp\require_utf8($text);
    \scpp\require_utf8($needle);
    $size = \mb_strlen($text, 'UTF-8');
    if ($offset < -$size || $offset > $size) { throw new \OutOfBoundsException('Text search offset is out of range'); }
    return \mb_strrpos($text, $needle, $offset, 'UTF-8');
}
function str_starts_with(string $text, string $prefix): bool {
    \scpp\require_utf8($text);
    \scpp\require_utf8($prefix);
    return \str_starts_with($text, $prefix);
}
function str_ends_with(string $text, string $suffix): bool {
    \scpp\require_utf8($text);
    \scpp\require_utf8($suffix);
    return \str_ends_with($text, $suffix);
}
}
