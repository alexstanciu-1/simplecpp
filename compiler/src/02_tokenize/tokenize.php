<?php
declare(strict_types=1);

/*
 * Role: Tokenize one fixed source buffer.
 * Used by: Tokenizer::run()
 * Call map: File_Tokenizer::tokenize() -> append(); fail() [invalid input]
 */

namespace tokenize;

use read_sources\Source_Buffer;
use read_sources\Source_Set;
use read_sources\file_change;

/**
 * @compiler-api Whole-file lexical process for compile; Parser consumes joined buffers.
 * Fixed source input, private output rows, no project symbol or type decisions.
 */
class File_Tokenizer
{
    // Lexical rules for the supported language subset; type names stay identifiers.
    private const LETTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
    private const DIGITS = '0123456789';
    private const IDENTIFIER_BYTES = self::LETTERS . self::DIGITS;
    private const WHITESPACE = " \t\r\n";
    private const KEYWORDS = [
        'template' => token_kind::template_keyword, 'typename' => token_kind::typename_keyword,
        'constexpr' => token_kind::constexpr_keyword, 'consteval' => token_kind::consteval_keyword,
        'const' => token_kind::const_keyword,
        'true' => token_kind::boolean_literal, 'false' => token_kind::boolean_literal,
        'function' => token_kind::function_keyword,
        'return' => token_kind::return_keyword,
        'if' => token_kind::if_keyword, 'else' => token_kind::else_keyword,
        'while' => token_kind::while_keyword, 'echo' => token_kind::echo_keyword,
        'new' => token_kind::new_keyword, 'struct' => token_kind::struct_keyword, 'public' => token_kind::public_keyword,
    ];
    private const PUNCTUATION = [
        '(' => token_kind::left_parenthesis, ')' => token_kind::right_parenthesis,
        '{' => token_kind::left_brace, '}' => token_kind::right_brace,
        '[' => token_kind::left_bracket, ']' => token_kind::right_bracket,
        '&' => token_kind::ampersand, ':' => token_kind::colon, ',' => token_kind::comma, ';' => token_kind::semicolon,
        '=' => token_kind::assignment, '+' => token_kind::plus,
        '<' => token_kind::left_angle, '>' => token_kind::right_angle,
    ];

    /**
     * @compiler-api Read exact source bytes and return tokens ending in one EOF token.
     * No source mutation. Unsupported/malformed syntax throws Source_Error; result still awaits join.
     */
    public static function tokenize(Source_Buffer $source): Token_Buffer
    {
        $tokens = new Token_Buffer($source);
        $text = $source->content; // PHP shares the string until a write; never modify it.
        $length = strlen($text);
        $offset = 0;

        // Consume trivia before classifying each token from its first source byte.
        while ($offset < $length)
        {
            $offset += strspn($text, self::WHITESPACE, $offset);
            if ($offset === $length) {
                break;
            }
            $start = $offset;
            $byte = $text[$offset];

            // Comments advance the source cursor without contributing token rows.
            if (($byte === '/') && (($offset + 1) < $length))
            {
                $next = $text[$offset + 1];
                if ($next === '/') {
                    $offset += 2;
                    $offset += strcspn($text, "\r\n", $offset);
                    continue;
                }
                if ($next === '*')
                {
                    $end = strpos($text, '*/', $offset + 2);
                    if ($end === false) {
                        self::fail($source, $start, $length - $start, 'Unterminated block comment');
                    }
                    $offset = $end + 2;
                    continue;
                }
            }

            // Variables and identifiers share spelling rules but have different keyword treatment.
            if (($byte === '$') || str_contains(self::LETTERS, $byte))
            {
                $variable = $byte === '$';
                if ($variable) {
                    ++$offset;
                    if (($offset === $length) || (!str_contains(self::LETTERS, $text[$offset]))) {
                        self::fail($source, $start, min(2, $length - $start), "Expected ASCII variable name after '$'");
                    }
                }
                $offset += strspn($text, self::IDENTIFIER_BYTES, $offset);

                // Variable spans include '$'; their names are never keyword tokens.
                $kind = $variable ? token_kind::variable_name
                    : (self::KEYWORDS[substr($text, $start, $offset - $start)] ?? token_kind::identifier);
            }
            elseif (($byte === '"') || ($byte === "'")) {
                $offset = self::quoted_end($source, $offset);
                $kind = token_kind::string_literal;
            }
            elseif (str_contains(self::DIGITS, $byte)) {
                $offset += strspn($text, self::DIGITS, $offset);
                if (($offset < $length) && (str_contains(self::IDENTIFIER_BYTES, $text[$offset]) || ($text[$offset] === '.'))) {
                    self::fail($source, $start, $offset - $start + 1, 'Unsupported or malformed numeric literal');
                }

                // Keep spelling only; range/type/base interpretation belongs downstream.
                $kind = token_kind::integer_literal;
            }
            elseif (substr($text, $offset, 2) === '->') {
                $kind = token_kind::field_arrow;
                $offset += 2;
            }
            elseif (substr($text, $offset, 2) === '<?') {
                self::fail($source, $start, 2, 'Unsupported source byte sequence <?');
            }
            elseif (isset(self::PUNCTUATION[$byte])) {
                // Equality and arrow tokens must not become separate assignments.
                if (($byte === '=') && (($offset + 1) < $length) && str_contains('=>', $text[$offset + 1])) {
                    self::fail($source, $start, 2, 'Unsupported operator starting with =');
                }
                $kind = self::PUNCTUATION[$byte];
                $offset++;
            }
            else {
                self::fail($source, $start, 1, sprintf('Unsupported source byte 0x%02X', ord($byte)));
            }
            self::append($tokens, $kind, $start, $offset - $start);
        }

        // Give every token snapshot one explicit terminator, including empty files.
        self::append($tokens, token_kind::end_of_file, $length, 0);
        return $tokens;
    }

    /** Find the closing quote without decoding bytes or assigning a runtime type. */
    private static function quoted_end(Source_Buffer $source, int $start): int
    {
        $text = $source->content;
        $length = strlen($text);
        for ($offset = $start + 1; $offset < $length; ++$offset)
        {
            if ($text[$offset] === $text[$start]) {
                return $offset + 1;
            }
            if ($text[$offset] === '\\') {
                ++$offset;
            }
        }
        self::fail($source, $start, $length - $start, 'Unterminated string literal');
    }

    private static function fail(Source_Buffer $source, int $start, int $length, string $reason): never
    {
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $start, $length, $reason);
    }

    private static function append(Token_Buffer $buffer, token_kind $kind, int $start, int $length): void
    {
        $token = new token();
        $token->kind = $kind;
        $token->start = $start;
        $token->length = $length;
        $buffer->rows[] = $token;
    }

}
