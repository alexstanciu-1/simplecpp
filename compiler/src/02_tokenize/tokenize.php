<?php
declare(strict_types=1);
namespace tokenize;

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
