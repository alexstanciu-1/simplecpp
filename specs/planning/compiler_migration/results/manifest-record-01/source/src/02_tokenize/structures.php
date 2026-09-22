<?php
declare(strict_types=1);

/*
 * Role: Token kinds and token rows.
 * Used by: Tokenizer; parser
 * Flow: source bytes -> token rows -> syntax
 */

namespace tokenize;
// <scpp-imports>
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

// Own token records; the eventual PHP++ port restores packed native layout.

// Supported lexical vocabulary; recognizing a token does not imply parser support.
/** @compiler-api Lexical vocabulary shared with parsing; recognition alone is not parser support. */
enum token_kind: int
{
    case invalid = 0;
    case end_of_file = 1;
    case identifier = 2;
    case integer_literal = 3;
    case function_keyword = 4;
    case return_keyword = 5;
    case left_parenthesis = 6;
    case right_parenthesis = 7;
    case left_brace = 8;
    case right_brace = 9;
    case colon = 10;
    case comma = 11;
    case semicolon = 12;
    case variable_name = 13;
    case assignment = 14;
    case plus = 15;
    case if_keyword = 16;
    case else_keyword = 17;
    case while_keyword = 18;
    case string_literal = 19;
    case echo_keyword = 20;
    case struct_keyword = 21;
    case public_keyword = 22;
    case new_keyword = 23;
    case field_arrow = 24;
    case left_angle = 25;
    case right_angle = 26;
    case template_keyword = 27;
    case typename_keyword = 28;
    case constexpr_keyword = 29;
    case consteval_keyword = 30;
    case const_keyword = 31;
    case boolean_literal = 32;
    case ampersand = 33;
    case left_bracket = 34;
    case right_bracket = 35;
}

// Byte offset and length in the token buffer's source file snapshot.
// Text stays in the source buffer; lengths are measured in bytes.
/**
 * @compiler-api Readable token row: kind, start and length. Offsets/lengths are bytes in the
 * owning Token_Buffer source. File_Tokenizer alone writes rows before handoff; no per-token identity across updates.
 */
class token {
    public int $start = 0;
    public int $length = 0;
    public token_kind $kind = token_kind::invalid;
}

/** @compiler-internal Stable debug spelling of the token vocabulary. */
class Token_Kinds
{
    public static function name(token_kind $kind): string
    {
        return enum_name($kind);
    }
}
