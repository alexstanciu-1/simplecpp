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
