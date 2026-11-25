<?php
declare(strict_types=1);

/*
 * Role: Syntax vocabulary and flat node rows.
 * Used by: File_Parser; Syntax_Access; syntax consumers
 * Flow: token spans -> node rows -> structural queries
 */
namespace parse;
// <scpp-imports>
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

// Own the common frontend syntax representation and its structural invariants.

/** @compiler-api AST node vocabulary; each kind requires explicit consumer support, not implied semantics. */
enum syntax_kind: int
{
    case invalid = 0;
    case function_declaration = 1;
    case parameter_list = 2;
    case block = 3;
    case return_statement = 4;
    case call_expression = 5;
    case name = 6;
    case integer_literal = 7;
    case expression_statement = 8;
    case file_root = 9;
    case variable_name = 10;
    case local_declaration = 11;
    case assignment_statement = 12;
    case parameter_declaration = 13;
    case addition_expression = 14;
    case if_statement = 15;
    case while_statement = 16;
    case string_literal = 17;
    case echo_statement = 18;
    case struct_declaration = 19;
    case field_declaration = 20;
    case field_expression = 21;
    case construct_expression = 22;
    case template_application = 23;
    case template_declaration = 24;
    case template_parameter_list = 25;
    case type_parameter_declaration = 26;
    case value_parameter_declaration = 27;
    case constant_declaration = 28;
    case type_annotation = 29;
    case constexpr_declaration = 30;
    case consteval_declaration = 31;
    case constexpr_if_statement = 32;
    case consteval_if_statement = 33;
    case boolean_literal = 34;
    case reference_annotation = 35;
    case const_reference_annotation = 36;
    case index_expression = 37;
    case method_declaration = 38;
    case less_than_expression = 39;
}

// IDs are one-based row positions within one Syntax_Tree snapshot; zero is absent.
// Nodes remain in append order. Child/sibling links describe logical order.
// No references into the vector survive an append; retain IDs instead.
/**
 * @compiler-api Readable flat node fields: byte span, kind and child/sibling IDs.
 * All links belong to the exact owning tree; parse alone mutates nodes before handoff.
 */
class syntax_node {
    public int $start = 0;
    public int $length = 0;
    public int $first_child_id = 0;
    public int $next_sibling_id = 0;
    public syntax_kind $kind = syntax_kind::invalid;
}

