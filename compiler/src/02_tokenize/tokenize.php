<?php
declare(strict_types=1);
namespace tokenize;
// <scpp-imports>
use function scpp\fs_read_snapshot as fs_read_snapshot;
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

final class File_Tokenizer {
    private static function letter(int $byte): bool {
        return (($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123)) || ($byte === 95);
    }
    private static function digit(int $byte): bool { return ($byte >= 48) && ($byte < 58); }
    private static function identifier(int $byte): bool { return File_Tokenizer::letter($byte) || File_Tokenizer::digit($byte); }
    private static function fail(Lexical_Buffer $buffer, int $start, int $length, string $reason): Lexical_Buffer {
        $empty /** vector<Token_Row> */ = [];
        $buffer->rows = $empty;
        $buffer->valid = false;
        $buffer->error_start = $start;
        $buffer->error_length = $length;
        $buffer->error_reason = $reason;
        return $buffer;
    }
    private static function append(Lexical_Buffer $buffer, int $kind, int $start, int $length): void {
        $row = new Token_Row();
        $row->start = $start;
        $row->length = $length;
        $row->kind = $kind;
        $buffer->rows[] = $row;
    }
    public static function tokenize(\read_sources\Source_Buffer $source): Lexical_Buffer {
        $buffer = new Lexical_Buffer($source);
        $text = $source->content;
        $length = string_byte_len($text);
        if ($length > 4294967295) { throw new \RuntimeException('Source exceeds uint32 token span capacity'); }
        $offset /** int */ = 0;
        while ($offset < $length) {
            $byte = string_byte_at($text, $offset);
            if (($byte === 32) || ($byte === 9) || ($byte === 13) || ($byte === 10)) { ++$offset; continue; }
            $start = $offset;
            $next = string_byte_at($text, $offset + 1);
            if ($byte === 47) {
                if ($next === 47) {
                    $offset = $offset + 2;
                    while ($offset < $length) {
                        $current = string_byte_at($text, $offset);
                        if (($current === 13) || ($current === 10)) { break; }
                        ++$offset;
                    }
                    continue;
                }
                if ($next === 42) {
                    $offset = $offset + 2;
                    $closed = false;
                    while ($offset < $length) {
                        if (string_byte_at($text, $offset) === 42) {
                            if (string_byte_at($text, $offset + 1) === 47) { $offset = $offset + 2; $closed = true; break; }
                        }
                        ++$offset;
                    }
                    if (!$closed) { return File_Tokenizer::fail($buffer, $start, $length - $start, 'Unterminated block comment'); }
                    continue;
                }
            }
            $kind /** int */ = 0;
            if (($byte === 36) || File_Tokenizer::letter($byte)) {
                $variable = $byte === 36;
                if ($variable) {
                    ++$offset;
                    if (!File_Tokenizer::letter(string_byte_at($text, $offset))) {
                        $error_length = $length - $start;
                        if ($error_length > 2) { $error_length = 2; }
                        return File_Tokenizer::fail($buffer, $start, $error_length, 'Expected ASCII variable name');
                    }
                }
                while ($offset < $length) {
                    if (!File_Tokenizer::identifier(string_byte_at($text, $offset))) { break; }
                    ++$offset;
                }
                $kind = \tokenize\TOKEN_VARIABLE_NAME;
                if (!$variable) { $kind = File_Tokenizer::keyword(string_byte_slice($text, $start, $offset - $start)); }
            } else if (($byte === 34) || ($byte === 39)) {
                ++$offset;
                $closed = false;
                while ($offset < $length) {
                    $current = string_byte_at($text, $offset);
                    if ($current === $byte) { ++$offset; $closed = true; break; }
                    if ($current === 92) { ++$offset; }
                    ++$offset;
                }
                if (!$closed) { return File_Tokenizer::fail($buffer, $start, $length - $start, 'Unterminated string literal'); }
                $kind = \tokenize\TOKEN_STRING_LITERAL;
            } else if (File_Tokenizer::digit($byte)) {
                while ($offset < $length) {
                    if (!File_Tokenizer::digit(string_byte_at($text, $offset))) { break; }
                    ++$offset;
                }
                $current = string_byte_at($text, $offset);
                if (File_Tokenizer::identifier($current) || ($current === 46)) {
                    return File_Tokenizer::fail($buffer, $start, $offset - $start + 1, 'Unsupported or malformed numeric literal');
                }
                $kind = \tokenize\TOKEN_INTEGER_LITERAL;
            } else if (($byte === 45) && ($next === 62)) {
                $offset = $offset + 2;
                $kind = \tokenize\TOKEN_FIELD_ARROW;
            } else {
                if (($byte === 60) && ($next === 63)) { return File_Tokenizer::fail($buffer, $start, 2, 'Unsupported source byte sequence <?'); }
                if ($byte === 61) {
                    if (($next === 61) || ($next === 62)) { return File_Tokenizer::fail($buffer, $start, 2, 'Unsupported operator starting with ='); }
                }
                $kind = File_Tokenizer::punctuation($byte);
                if ($kind === 0) { return File_Tokenizer::fail($buffer, $start, 1, 'Unsupported source byte'); }
                ++$offset;
            }
            File_Tokenizer::append($buffer, $kind, $start, $offset - $start);
        }
        File_Tokenizer::append($buffer, \tokenize\TOKEN_END_OF_FILE, $length, 0);
        return $buffer;
    }
    private static function keyword(string $text): int {
        if ($text === 'template') { return \tokenize\TOKEN_TEMPLATE_KEYWORD; }
        if ($text === 'typename') { return \tokenize\TOKEN_TYPENAME_KEYWORD; }
        if ($text === 'constexpr') { return \tokenize\TOKEN_CONSTEXPR_KEYWORD; }
        if ($text === 'consteval') { return \tokenize\TOKEN_CONSTEVAL_KEYWORD; }
        if ($text === 'const') { return \tokenize\TOKEN_CONST_KEYWORD; }
        if ($text === 'true') { return \tokenize\TOKEN_BOOLEAN_LITERAL; }
        if ($text === 'false') { return \tokenize\TOKEN_BOOLEAN_LITERAL; }
        if ($text === 'function') { return \tokenize\TOKEN_FUNCTION_KEYWORD; }
        if ($text === 'return') { return \tokenize\TOKEN_RETURN_KEYWORD; }
        if ($text === 'if') { return \tokenize\TOKEN_IF_KEYWORD; }
        if ($text === 'else') { return \tokenize\TOKEN_ELSE_KEYWORD; }
        if ($text === 'while') { return \tokenize\TOKEN_WHILE_KEYWORD; }
        if ($text === 'echo') { return \tokenize\TOKEN_ECHO_KEYWORD; }
        if ($text === 'new') { return \tokenize\TOKEN_NEW_KEYWORD; }
        if ($text === 'struct') { return \tokenize\TOKEN_STRUCT_KEYWORD; }
        if ($text === 'public') { return \tokenize\TOKEN_PUBLIC_KEYWORD; }
        return \tokenize\TOKEN_IDENTIFIER;
    }
    private static function punctuation(int $byte): int {
        if ($byte === 40) { return \tokenize\TOKEN_LEFT_PARENTHESIS; }
        if ($byte === 41) { return \tokenize\TOKEN_RIGHT_PARENTHESIS; }
        if ($byte === 123) { return \tokenize\TOKEN_LEFT_BRACE; }
        if ($byte === 125) { return \tokenize\TOKEN_RIGHT_BRACE; }
        if ($byte === 91) { return \tokenize\TOKEN_LEFT_BRACKET; }
        if ($byte === 93) { return \tokenize\TOKEN_RIGHT_BRACKET; }
        if ($byte === 38) { return \tokenize\TOKEN_AMPERSAND; }
        if ($byte === 58) { return \tokenize\TOKEN_COLON; }
        if ($byte === 44) { return \tokenize\TOKEN_COMMA; }
        if ($byte === 59) { return \tokenize\TOKEN_SEMICOLON; }
        if ($byte === 61) { return \tokenize\TOKEN_ASSIGNMENT; }
        if ($byte === 43) { return \tokenize\TOKEN_PLUS; }
        if ($byte === 60) { return \tokenize\TOKEN_LEFT_ANGLE; }
        if ($byte === 62) { return \tokenize\TOKEN_RIGHT_ANGLE; }
        return 0;
    }
}
