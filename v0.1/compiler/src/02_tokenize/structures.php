<?php
declare(strict_types=1);
namespace tokenize;

/** @scpp-struct */
final class Token_Row {
    public int $start /** uint32 */ = 0;
    public int $length /** uint32 */ = 0;
    public int $kind /** uint32 */ = 0;
}
const TOKEN_INVALID = 0;
const TOKEN_END_OF_FILE = 1;
const TOKEN_IDENTIFIER = 2;
const TOKEN_INTEGER_LITERAL = 3;
const TOKEN_FUNCTION_KEYWORD = 4;
const TOKEN_RETURN_KEYWORD = 5;
const TOKEN_LEFT_PARENTHESIS = 6;
const TOKEN_RIGHT_PARENTHESIS = 7;
const TOKEN_LEFT_BRACE = 8;
const TOKEN_RIGHT_BRACE = 9;
const TOKEN_COLON = 10;
const TOKEN_COMMA = 11;
const TOKEN_SEMICOLON = 12;
const TOKEN_VARIABLE_NAME = 13;
const TOKEN_ASSIGNMENT = 14;
const TOKEN_PLUS = 15;
const TOKEN_IF_KEYWORD = 16;
const TOKEN_ELSE_KEYWORD = 17;
const TOKEN_WHILE_KEYWORD = 18;
const TOKEN_STRING_LITERAL = 19;
const TOKEN_ECHO_KEYWORD = 20;
const TOKEN_STRUCT_KEYWORD = 21;
const TOKEN_PUBLIC_KEYWORD = 22;
const TOKEN_NEW_KEYWORD = 23;
const TOKEN_FIELD_ARROW = 24;
const TOKEN_LEFT_ANGLE = 25;
const TOKEN_RIGHT_ANGLE = 26;
const TOKEN_TEMPLATE_KEYWORD = 27;
const TOKEN_TYPENAME_KEYWORD = 28;
const TOKEN_CONSTEXPR_KEYWORD = 29;
const TOKEN_CONSTEVAL_KEYWORD = 30;
const TOKEN_CONST_KEYWORD = 31;
const TOKEN_BOOLEAN_LITERAL = 32;
const TOKEN_AMPERSAND = 33;
const TOKEN_LEFT_BRACKET = 34;
const TOKEN_RIGHT_BRACKET = 35;
/** Stable integer vocabulary permits compact rows without per-token enum objects. */
final class Token_Kinds {
    public static function name(int $kind): string {
        if ($kind === 0) { return 'invalid'; }
        if ($kind === 1) { return 'end_of_file'; }
        if ($kind === 2) { return 'identifier'; }
        if ($kind === 3) { return 'integer_literal'; }
        if ($kind === 4) { return 'function_keyword'; }
        if ($kind === 5) { return 'return_keyword'; }
        if ($kind === 6) { return 'left_parenthesis'; }
        if ($kind === 7) { return 'right_parenthesis'; }
        if ($kind === 8) { return 'left_brace'; }
        if ($kind === 9) { return 'right_brace'; }
        if ($kind === 10) { return 'colon'; }
        if ($kind === 11) { return 'comma'; }
        if ($kind === 12) { return 'semicolon'; }
        if ($kind === 13) { return 'variable_name'; }
        if ($kind === 14) { return 'assignment'; }
        if ($kind === 15) { return 'plus'; }
        if ($kind === 16) { return 'if_keyword'; }
        if ($kind === 17) { return 'else_keyword'; }
        if ($kind === 18) { return 'while_keyword'; }
        if ($kind === 19) { return 'string_literal'; }
        if ($kind === 20) { return 'echo_keyword'; }
        if ($kind === 21) { return 'struct_keyword'; }
        if ($kind === 22) { return 'public_keyword'; }
        if ($kind === 23) { return 'new_keyword'; }
        if ($kind === 24) { return 'field_arrow'; }
        if ($kind === 25) { return 'left_angle'; }
        if ($kind === 26) { return 'right_angle'; }
        if ($kind === 27) { return 'template_keyword'; }
        if ($kind === 28) { return 'typename_keyword'; }
        if ($kind === 29) { return 'constexpr_keyword'; }
        if ($kind === 30) { return 'consteval_keyword'; }
        if ($kind === 31) { return 'const_keyword'; }
        if ($kind === 32) { return 'boolean_literal'; }
        if ($kind === 33) { return 'ampersand'; }
        if ($kind === 34) { return 'left_bracket'; }
        if ($kind === 35) { return 'right_bracket'; }
        return 'unknown';
    }
}
