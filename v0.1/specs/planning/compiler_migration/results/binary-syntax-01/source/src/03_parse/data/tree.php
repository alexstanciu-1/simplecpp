<?php
declare(strict_types=1);

/* Flat syntax tree storage; indexed frontend sets remain in store.php. */
namespace parse;
// <scpp-imports>
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

/**
 * @compiler-api Readable flat AST: source_file_id, root_node_id and nodes, built only by parse.
 * Node IDs are one-based positions in this exact tree; zero is absent. Consumers may
 * read rows/links directly; use Syntax_Access for named structural roles. Treat rows
 * as immutable after handoff.
 *
 * Child order:
 * file_root: implicit entry block, then top-level definitions in source order
 * block: statements in source order
 * function_declaration: name, parameter_list, return-type syntax (name), block
 * parameter_list: parameter declarations in source order
 * parameter_declaration: variable name, type syntax, optional const/mutable reference annotation
 * return_statement: optional value expression
 * expression_statement: value expression
 * local_declaration: variable name, type name, initializer expression
 * assignment_statement: target variable, value expression
 * call_expression: callee name, then argument expressions in source order
 * name/variable_name/integer_literal: leaves; spelling stays in source spans
 */
class Syntax_Tree {
    public int $source_file_id = 0;
    public int $root_node_id = 0;

    // Flat syntax storage. File_Frontend's entity/body IDs index this same tree.
    /** @var list<syntax_node> */
    public array $nodes /** vector<syntax_node> */ = [];
}

