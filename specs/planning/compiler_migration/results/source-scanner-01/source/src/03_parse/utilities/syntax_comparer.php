<?php
declare(strict_types=1);

/*
 * Role: Compare syntax logically, independent of offsets and IDs.
 * Used by: Comparison_Worker::compare()
 * Call map:
 *   Syntax_Comparer::equal()
 *     -> [action] traverse paired subtrees without mutation
 */

namespace parse;
// <scpp-imports>
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

// Logical syntax equality only: no type equivalence, evaluation or AST mutation.
/** @compiler-api Logical subtree comparison used by symbol change cataloging; read-only syntax API. */
class Syntax_Comparer
{
    /**
     * @compiler-api Compare kind, spelling and ordered descendants, excluding each root's own siblings.
     * Offsets/whitespace are ignored. Zero roots represent absence. No type equality or
     * work-selection decision; unknown kinds/invalid nodes throw LogicException.
     */
    public static function equal(File_Frontend $previous, int $previous_root, File_Frontend $current, int $current_root): bool
    {
        // Typed traversal frames; selected roots' own siblings stay outside the boundary.
        $pending = new Comparison_Stack();
        $pending->push($previous_root, $current_root, false);
        while (!$pending->is_empty())
        {
            $frame = $pending->pop();
            $siblings = $frame->siblings;
            $right_id = $frame->right;
            $left_id = $frame->left;
            if (($left_id === 0) || ($right_id === 0)) {
                if ($left_id !== $right_id) {
                    return false;
                }
                continue;
            }
            if (($left_id < 1) || ($left_id > count($previous->syntax->nodes))) {
                throw new \LogicException('Invalid previous comparison node');
            }
            $left = $previous->syntax->nodes[$left_id - 1];
            if (($right_id < 1) || ($right_id > count($current->syntax->nodes))) {
                throw new \LogicException('Invalid current comparison node');
            }
            $right = $current->syntax->nodes[$right_id - 1];
            if ($left->kind !== $right->kind) {
                return false;
            }

            // These syntax kinds carry no scalar payload besides their spelling
            // or ordered children. Adding a kind must establish its equality rule.
            $has_spelling = Syntax_Comparer::has_spelling($left->kind);
            if (($has_spelling) && (($left->length !== $right->length)
                    || (string_byte_slice($previous->tokens->source->content, $left->start, $left->length)
                        !== string_byte_slice($current->tokens->source->content, $right->start, $right->length)))) {
                return false;
            }
            if (($siblings) && (($left->next_sibling_id !== 0) || ($right->next_sibling_id !== 0))) {
                $pending->push($left->next_sibling_id, $right->next_sibling_id, true);
            }
            if (($left->first_child_id !== 0) || ($right->first_child_id !== 0)) {
                $pending->push($left->first_child_id, $right->first_child_id, true);
            }
        }
        return true;
    }
    private static function has_spelling(syntax_kind $kind): bool
    {
        if (($kind === syntax_kind::name)
            || ($kind === syntax_kind::variable_name)
            || ($kind === syntax_kind::integer_literal)
            || ($kind === syntax_kind::string_literal)
            || ($kind === syntax_kind::boolean_literal)) { return true; }
        if (($kind !== syntax_kind::template_application)
            && ($kind !== syntax_kind::template_declaration)
            && ($kind !== syntax_kind::template_parameter_list)
            && ($kind !== syntax_kind::type_parameter_declaration)
            && ($kind !== syntax_kind::value_parameter_declaration)
            && ($kind !== syntax_kind::constant_declaration)
            && ($kind !== syntax_kind::type_annotation)
            && ($kind !== syntax_kind::constexpr_declaration)
            && ($kind !== syntax_kind::consteval_declaration)
            && ($kind !== syntax_kind::constexpr_if_statement)
            && ($kind !== syntax_kind::consteval_if_statement)
            && ($kind !== syntax_kind::struct_declaration)
            && ($kind !== syntax_kind::field_declaration)
            && ($kind !== syntax_kind::method_declaration)
            && ($kind !== syntax_kind::construct_expression)
            && ($kind !== syntax_kind::field_expression)
            && ($kind !== syntax_kind::index_expression)
            && ($kind !== syntax_kind::file_root)
            && ($kind !== syntax_kind::function_declaration)
            && ($kind !== syntax_kind::reference_annotation)
            && ($kind !== syntax_kind::const_reference_annotation)
            && ($kind !== syntax_kind::parameter_list)
            && ($kind !== syntax_kind::parameter_declaration)
            && ($kind !== syntax_kind::block)
            && ($kind !== syntax_kind::return_statement)
            && ($kind !== syntax_kind::call_expression)
            && ($kind !== syntax_kind::expression_statement)
            && ($kind !== syntax_kind::local_declaration)
            && ($kind !== syntax_kind::assignment_statement)
            && ($kind !== syntax_kind::addition_expression)
            && ($kind !== syntax_kind::less_than_expression)
            && ($kind !== syntax_kind::if_statement)
            && ($kind !== syntax_kind::while_statement)
            && ($kind !== syntax_kind::echo_statement)) {
            throw new \LogicException('Unsupported syntax kind for comparison: ' . Syntax_Kinds::name($kind));
        }
        return false;
    }

}

/** @compiler-internal One pending subtree pair; consumed before stack reuse. */
class Comparison_Frame
{
    public int $left = 0;
    public int $right = 0;
    public bool $siblings = false;
}

/** @compiler-internal Reusable typed scratch storage for iterative comparison. */
class Comparison_Stack
{
    private array $frames /** vector<Comparison_Frame> */ = [];
    private int $used = 0;

    public function push(int $left, int $right, bool $siblings): void
    {
        if ($this->used === count($this->frames)) { $this->frames[] = new Comparison_Frame(); }
        $frame = $this->frames[$this->used];
        $frame->left = $left; $frame->right = $right; $frame->siblings = $siblings;
        $this->used++;
    }

    public function pop(): Comparison_Frame
    {
        if ($this->used === 0) { throw new \LogicException('Empty syntax comparison stack'); }
        $this->used = $this->used - 1;
        return $this->frames[$this->used];
    }

    public function is_empty(): bool { return $this->used === 0; }
}
