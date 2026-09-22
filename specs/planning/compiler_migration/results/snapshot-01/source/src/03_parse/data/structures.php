<?php
declare(strict_types=1);

/*
 * Role: Expression contexts and mutable cursors; nodes and role views have separate owners.
 * Used by: File_Parser; Syntax_Access; syntax consumers
 * Flow: tokens -> syntax nodes -> structural queries
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

/** The expression driver uses these syntax contexts without consulting symbols or types. */
enum expression_context: int
{
    case value = 0;
    case type = 1;
    case group = 2;
    case call_arguments = 3;
    case template_arguments = 4;
    case constructed_type = 5;
    case index = 6;
}

/** @compiler-internal Private continuation: expression accumulation and an optional enclosing syntax node. */
class expression_cursor
{
    /** @var list<int> Private operand syntax IDs awaiting precedence reduction. */
    public array $operands /** vector<int> */ = [];
    /** @var list<syntax_kind> */
    public array $operators /** vector<syntax_kind> */ = [];

    public function __construct(public readonly expression_context $context = expression_context::value,
        public readonly int $node_id = 0, public int $last_child_id = 0)
    {
    }
}

