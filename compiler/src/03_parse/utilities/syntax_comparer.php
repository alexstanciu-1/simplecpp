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

// Logical syntax equality only: no type equivalence, evaluation or AST mutation.
/** @compiler-api Logical subtree comparison used by symbol change cataloging; read-only syntax API. */
class Syntax_Comparer
{
    /**
     * @compiler-api Compare kind, spelling and ordered descendants, excluding each root's own siblings.
     * Offsets/whitespace are ignored. Zero roots represent absence. No type equality or
     * work-selection decision; unknown kinds/invalid nodes throw LogicException.
     */
    public static function equal(Parse_Result $previous, int $previous_root, Parse_Result $current, int $current_root): bool
    {
        if (($previous_root === 0) || ($current_root === 0)) { return $previous_root === $current_root; }
        if (($previous_root < 1) || ($previous_root > $previous->tree->size())) { throw new \LogicException('Invalid previous comparison node'); }
        if (($current_root < 1) || ($current_root > $current->tree->size())) { throw new \LogicException('Invalid current comparison node'); }
        // Typed traversal frames; selected roots' own siblings stay outside the boundary.
        $pending = new Comparison_Stack();
        $pending->push($previous_root, $current_root, false);
        while (!$pending->is_empty())
        {
            $frame = $pending->pop();
            $siblings = $frame->siblings;
            $right_id = (int)$frame->right;
            $left_id = (int)$frame->left;
            if (($left_id === 0) || ($right_id === 0)) {
                if ($left_id !== $right_id) {
                    return false;
                }
                continue;
            }
            if (($left_id < 1) || ($left_id > $previous->tree->size())) {
                throw new \LogicException('Invalid previous comparison node');
            }
            $left = $previous->tree->row($left_id);
            if (($right_id < 1) || ($right_id > $current->tree->size())) {
                throw new \LogicException('Invalid current comparison node');
            }
            $right = $current->tree->row($right_id);
            if ((int)$left->kind !== (int)$right->kind) {
                return false;
            }

            // These syntax kinds carry no scalar payload besides their spelling
            // or ordered children. Adding a kind must establish its equality rule.
            $has_spelling = Syntax_Comparer::has_spelling((int)$left->kind);
            if ($has_spelling) {
                if ((int)$left->length !== (int)$right->length) { return false; }
                if (string_byte_slice($previous->tokens->source->content, (int)$left->start, (int)$left->length)
                    !== string_byte_slice($current->tokens->source->content, (int)$right->start, (int)$right->length)) { return false; }
            }
            if (($siblings) && (((int)$left->next_sibling !== 0) || ((int)$right->next_sibling !== 0))) {
                $pending->push((int)$left->next_sibling, (int)$right->next_sibling, true);
            }
            if (((int)$left->first_child !== 0) || ((int)$right->first_child !== 0)) {
                $pending->push((int)$left->first_child, (int)$right->first_child, true);
            }
        }
        return true;
    }
    private static function has_spelling(int $kind): bool
    {
        if (($kind === \parse\SYNTAX_NAME)
            || ($kind === \parse\SYNTAX_VARIABLE_NAME)
            || ($kind === \parse\SYNTAX_INTEGER_LITERAL)
            || ($kind === \parse\SYNTAX_STRING_LITERAL)
            || ($kind === \parse\SYNTAX_BOOLEAN_LITERAL)) { return true; }
        if (($kind !== \parse\SYNTAX_TEMPLATE_APPLICATION)
            && ($kind !== \parse\SYNTAX_TEMPLATE_DECLARATION)
            && ($kind !== \parse\SYNTAX_TEMPLATE_PARAMETER_LIST)
            && ($kind !== \parse\SYNTAX_TYPE_PARAMETER_DECLARATION)
            && ($kind !== \parse\SYNTAX_VALUE_PARAMETER_DECLARATION)
            && ($kind !== \parse\SYNTAX_CONSTANT_DECLARATION)
            && ($kind !== \parse\SYNTAX_TYPE_ANNOTATION)
            && ($kind !== \parse\SYNTAX_CONSTEXPR_DECLARATION)
            && ($kind !== \parse\SYNTAX_CONSTEVAL_DECLARATION)
            && ($kind !== \parse\SYNTAX_CONSTEXPR_IF_STATEMENT)
            && ($kind !== \parse\SYNTAX_CONSTEVAL_IF_STATEMENT)
            && ($kind !== \parse\SYNTAX_STRUCT_DECLARATION)
            && ($kind !== \parse\SYNTAX_FIELD_DECLARATION)
            && ($kind !== \parse\SYNTAX_METHOD_DECLARATION)
            && ($kind !== \parse\SYNTAX_CONSTRUCT_EXPRESSION)
            && ($kind !== \parse\SYNTAX_FIELD_EXPRESSION)
            && ($kind !== \parse\SYNTAX_INDEX_EXPRESSION)
            && ($kind !== \parse\SYNTAX_FILE_ROOT)
            && ($kind !== \parse\SYNTAX_FUNCTION_DECLARATION)
            && ($kind !== \parse\SYNTAX_REFERENCE_ANNOTATION)
            && ($kind !== \parse\SYNTAX_CONST_REFERENCE_ANNOTATION)
            && ($kind !== \parse\SYNTAX_PARAMETER_LIST)
            && ($kind !== \parse\SYNTAX_PARAMETER_DECLARATION)
            && ($kind !== \parse\SYNTAX_BLOCK)
            && ($kind !== \parse\SYNTAX_RETURN_STATEMENT)
            && ($kind !== \parse\SYNTAX_CALL_EXPRESSION)
            && ($kind !== \parse\SYNTAX_EXPRESSION_STATEMENT)
            && ($kind !== \parse\SYNTAX_LOCAL_DECLARATION)
            && ($kind !== \parse\SYNTAX_ASSIGNMENT_STATEMENT)
            && ($kind !== \parse\SYNTAX_ADDITION_EXPRESSION)
            && ($kind !== \parse\SYNTAX_LESS_THAN_EXPRESSION)
            && ($kind !== \parse\SYNTAX_IF_STATEMENT)
            && ($kind !== \parse\SYNTAX_WHILE_STATEMENT)
            && ($kind !== \parse\SYNTAX_ECHO_STATEMENT)) {
            throw new \LogicException('Unsupported syntax kind for comparison: ' . $kind);
        }
        return false;
    }

}

/** @compiler-internal One pending subtree pair; consumed before stack reuse. */
/** @scpp-struct */
final class Comparison_Frame
{
    public int $left /** uint32 */ = 0;
    public int $right /** uint32 */ = 0;
    public bool $siblings = false;
}

/** @compiler-internal Reusable typed scratch storage for iterative comparison. */
class Comparison_Stack
{
    private array $frames /** vector<Comparison_Frame> */ = [];
    private int $used = 0;

    public function push(int $left, int $right, bool $siblings): void
    {
        $frame = new Comparison_Frame();
        $frame->left = $left;
        $frame->right = $right;
        $frame->siblings = $siblings;
        if ($this->used === q_count($this->frames)) { $this->frames[] = $frame; }
        else { $this->frames[$this->used] = $frame; }
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
