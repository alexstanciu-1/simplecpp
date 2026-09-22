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
// Frozen pre-adaptation oracle; only the class name differs.
class Baseline_Syntax_Comparer
{
    /**
     * @compiler-api Compare kind, spelling and ordered descendants, excluding each root's own siblings.
     * Offsets/whitespace are ignored. Zero roots represent absence. No type equality or
     * work-selection decision; unknown kinds/invalid nodes throw LogicException.
     */
    public static function equal(File_Frontend $previous, int $previous_root, File_Frontend $current, int $current_root): bool
    {
        // Flat traversal stack: pairs of node IDs plus whether to follow siblings.
        // The selected roots' own siblings are outside the comparison boundary.
        $pending = [$previous_root, $current_root, false];
        while ($pending !== [])
        {
            $siblings = array_pop($pending);
            $right_id = array_pop($pending);
            $left_id = array_pop($pending);
            if (($left_id === 0) || ($right_id === 0)) {
                if ($left_id !== $right_id) {
                    return false;
                }
                continue;
            }
            $left = $previous->syntax->nodes[$left_id - 1] ?? throw new \LogicException('Invalid previous comparison node');
            $right = $current->syntax->nodes[$right_id - 1] ?? throw new \LogicException('Invalid current comparison node');
            if ($left->kind !== $right->kind) {
                return false;
            }

            // These syntax kinds carry no scalar payload besides their spelling
            // or ordered children. Adding a kind must establish its equality rule.
            $has_spelling = match ($left->kind)
            {
                syntax_kind::name, syntax_kind::variable_name, syntax_kind::integer_literal, syntax_kind::string_literal, syntax_kind::boolean_literal => true,
                syntax_kind::template_application, syntax_kind::template_declaration, syntax_kind::template_parameter_list,
                syntax_kind::type_parameter_declaration, syntax_kind::value_parameter_declaration,
                syntax_kind::constant_declaration, syntax_kind::type_annotation,
                syntax_kind::constexpr_declaration, syntax_kind::consteval_declaration,
                syntax_kind::constexpr_if_statement, syntax_kind::consteval_if_statement,
                syntax_kind::struct_declaration, syntax_kind::field_declaration, syntax_kind::method_declaration, syntax_kind::construct_expression, syntax_kind::field_expression, syntax_kind::index_expression,
                syntax_kind::file_root, syntax_kind::function_declaration,
                syntax_kind::reference_annotation, syntax_kind::const_reference_annotation,
                syntax_kind::parameter_list, syntax_kind::parameter_declaration, syntax_kind::block,
                syntax_kind::return_statement, syntax_kind::call_expression,
                syntax_kind::expression_statement, syntax_kind::local_declaration,
                syntax_kind::assignment_statement, syntax_kind::addition_expression, syntax_kind::less_than_expression,
                syntax_kind::if_statement, syntax_kind::while_statement, syntax_kind::echo_statement => false,
                default => throw new \LogicException('Unsupported syntax kind for comparison: ' . $left->kind->name),
            };
            if (($has_spelling) && (($left->length !== $right->length)
                    || (substr($previous->tokens->source->content, $left->start, $left->length)
                        !== substr($current->tokens->source->content, $right->start, $right->length)))) {
                return false;
            }
            if (($siblings) && (($left->next_sibling_id !== 0) || ($right->next_sibling_id !== 0))) {
                $pending[] = $left->next_sibling_id;
                $pending[] = $right->next_sibling_id;
                $pending[] = true;
            }
            if (($left->first_child_id !== 0) || ($right->first_child_id !== 0)) {
                $pending[] = $left->first_child_id;
                $pending[] = $right->first_child_id;
                $pending[] = true;
            }
        }
        return true;
    }
}
