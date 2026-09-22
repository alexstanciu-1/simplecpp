<?php
declare(strict_types=1);

/*
 * Role: Expose validated structural roles as existing node IDs.
 * Used by: Parsing and syntax-consuming analysis
 * Call map:
 *   Syntax_Access::function_parts(); struct_parts(); field_declaration_parts(); control_parts()
 *     -> [action] check child roles and return structural parts
 *   Metaprogramming_Syntax supplies template/constant/type-root views on this owner
 */

namespace parse;
// <scpp-imports>
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

// Structural roles only. Name binding, type meaning and change policy belong
// to consumers. Accessors return local IDs; they never copy or edit syntax.
/**
 * @compiler-api Structural query boundary for semantic consumers; fixed tree in, local IDs out.
 * Methods throw LogicException for wrong/malformed shapes and never modify the tree.
 * These queries validate their documented roles, not an entire AST.
 */
class Syntax_Access
{
    use Metaprogramming_Syntax;

    /** @compiler-api Read the function child roles; return node IDs in this tree, with no semantic resolution. */
    public static function function_parts(Syntax_Tree $tree, int $id): function_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed function declaration');
        if ($node->kind !== syntax_kind::function_declaration) {
            throw new \LogicException('Expected a parsed function declaration');
        }
        $name = Syntax_Access::leaf($tree, $node->first_child_id, syntax_kind::name);
        $parameters = Syntax_Access::require_node($tree, $name->next_sibling_id, 'Invalid function parameter list');
        if ($parameters->kind !== syntax_kind::parameter_list) {
            throw new \LogicException('Invalid function parameter list');
        }
        $type = Syntax_Access::type_syntax($tree, $parameters->next_sibling_id);
        $body = Syntax_Access::require_node($tree, $type->next_sibling_id, 'Invalid function body or unexpected declaration child');
        if (($body->kind !== syntax_kind::block) || ($body->next_sibling_id !== 0)) {
            throw new \LogicException('Invalid function body or unexpected declaration child');
        }
        return new function_parts($node->first_child_id, $name->next_sibling_id,
            $parameters->next_sibling_id, $type->next_sibling_id);
    }

    /** @compiler-api Read the struct name and first field; semantic eligibility belongs to resolution. */
    public static function struct_parts(Syntax_Tree $tree, int $id): struct_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed struct declaration');
        if ($node->kind !== syntax_kind::struct_declaration) {
            throw new \LogicException('Expected a parsed struct declaration');
        }
        $name = Syntax_Access::leaf($tree, $node->first_child_id, syntax_kind::name);
        $first = $name->next_sibling_id;
        if (($first !== 0) && !((Syntax_Access::is_kind($tree, $first, syntax_kind::field_declaration))
            || (Syntax_Access::is_kind($tree, $first, syntax_kind::method_declaration)))) {
            throw new \LogicException('Invalid parsed struct field');
        }
        return new struct_parts($node->first_child_id, $first);
    }

    /** Return a lazy typed member traversal; validation starts on the first advance. */
    public static function struct_members(Syntax_Tree $tree, int $declaration, syntax_kind $kind): Struct_Member_Cursor
    {
        return new Struct_Member_Cursor($tree, $declaration, $kind);
    }

    /** The method wrapper carries receiver access; its function subtree remains ordinary syntax. */
    public static function const_receiver(Syntax_Tree $tree, int $id): bool
    {
        $node = $tree->nodes[$id - 1];
        if ($node->kind !== syntax_kind::method_declaration) {
            throw new \LogicException('Expected method declaration');
        }
        return $tree->nodes[$node->first_child_id - 1]->next_sibling_id !== 0;
    }

    /** @compiler-api Read a public field's type/name roles without interpreting its type or visibility. */
    public static function field_declaration_parts(Syntax_Tree $tree, int $id): field_declaration_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed field declaration');
        if ($node->kind !== syntax_kind::field_declaration) {
            throw new \LogicException('Expected a parsed field declaration');
        }
        $type = Syntax_Access::type_syntax($tree, $node->first_child_id);
        $variable_id = $type->next_sibling_id;
        $variable = Syntax_Access::leaf($tree, $variable_id, syntax_kind::variable_name);
        $extent = $variable->next_sibling_id;
        if (($extent !== 0) && (Syntax_Access::expression($tree, $extent)->next_sibling_id !== 0)) {
            throw new \LogicException('Unexpected field declaration child');
        }
        return new field_declaration_parts($node->first_child_id, $variable_id, $extent);
    }

    /** @compiler-api Read the callee syntax node ID of a call expression. */
    public static function call_target(Syntax_Tree $tree, int $id): int
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed call');
        if ($node->kind !== syntax_kind::call_expression) {
            throw new \LogicException('Expected a parsed call');
        }
        if (!Syntax_Access::is_kind($tree, $node->first_child_id, syntax_kind::field_expression)) {
            Syntax_Access::type_syntax($tree, $node->first_child_id);
        }
        return $node->first_child_id;
    }

    // Arguments are the target's following siblings, in source order. No
    // extra list node or copied argument index is needed, including empty calls.
    /** @compiler-api Read the first argument expression ID or zero; follow siblings for source order. */
    public static function first_argument(Syntax_Tree $tree, int $id): int
    {
        $target = Syntax_Access::call_target($tree, $id);
        $first = $tree->nodes[$target - 1]->next_sibling_id;
        if ($first !== 0) {
            Syntax_Access::expression($tree, $first);
        }
        return $first;
    }

    /** @compiler-api Read the first parameter declaration ID or zero from a parameter-list node. */
    public static function first_parameter(Syntax_Tree $tree, int $list_id): int
    {
        $list = Syntax_Access::require_node($tree, $list_id, 'Expected parsed parameter list');
        if ($list->kind !== syntax_kind::parameter_list) {
            throw new \LogicException('Expected parsed parameter list');
        }
        $first = $list->first_child_id;
        if (($first !== 0) && (!Syntax_Access::is_kind($tree, $first, syntax_kind::parameter_declaration))) {
            throw new \LogicException('Invalid parsed parameter list child');
        }
        return $first;
    }

    /** @compiler-api Read parameter variable/type-syntax child roles; no name/type interpretation. */
    public static function parameter_parts(Syntax_Tree $tree, int $id): parameter_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed parameter declaration');
        if ($node->kind !== syntax_kind::parameter_declaration) {
            throw new \LogicException('Expected parsed parameter declaration');
        }
        $variable_id = $node->first_child_id;
        $variable = Syntax_Access::leaf($tree, $variable_id, syntax_kind::variable_name);
        $type_id = $variable->next_sibling_id;
        $type = Syntax_Access::type_syntax($tree, $type_id);
        if ($type->next_sibling_id !== 0)
        {
            $modifier = Syntax_Access::require_node($tree, $type->next_sibling_id, 'Unexpected parameter declaration child');
            if (($modifier->first_child_id !== 0) || ($modifier->next_sibling_id !== 0)
                || !(($modifier->kind === syntax_kind::reference_annotation) || ($modifier->kind === syntax_kind::const_reference_annotation))) {
                throw new \LogicException('Unexpected parameter declaration child');
            }
            return new parameter_parts($variable_id, $type_id, $modifier->kind);
        }
        return new parameter_parts($variable_id, $type_id);
    }

    /** @compiler-api Read variable/type-syntax/initializer roles of a local declaration. */
    public static function local_declaration_parts(Syntax_Tree $tree, int $id): local_declaration_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed local declaration');
        if ($node->kind !== syntax_kind::local_declaration) {
            throw new \LogicException('Expected a parsed local declaration');
        }
        $variable_id = $node->first_child_id;
        $variable = Syntax_Access::leaf($tree, $variable_id, syntax_kind::variable_name);
        $type_id = $variable->next_sibling_id;
        $type = Syntax_Access::type_syntax($tree, $type_id);
        $initializer_id = $type->next_sibling_id;
        if (($initializer_id !== 0) && (Syntax_Access::expression($tree, $initializer_id)->next_sibling_id !== 0)) {
            throw new \LogicException('Unexpected local declaration child');
        }
        return new local_declaration_parts($variable_id, $type_id, $initializer_id);
    }

    /** @compiler-api Read target/value roles of an assignment. */
    public static function assignment_parts(Syntax_Tree $tree, int $id): assignment_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed assignment');
        if ($node->kind !== syntax_kind::assignment_statement) {
            throw new \LogicException('Expected a parsed assignment');
        }
        $target_id = $node->first_child_id;
        $target = Syntax_Access::expression($tree, $target_id);
        $value_id = $target->next_sibling_id;
        $value = Syntax_Access::expression($tree, $value_id);
        if ($value->next_sibling_id !== 0) {
            throw new \LogicException('Unexpected assignment child');
        }
        return new assignment_parts($target_id, $value_id);
    }

    // Zero means a bare return; expression statements always have one child.
    /** @compiler-api Read the expression ID; zero denotes a bare return, not an absent expression statement. */
    public static function statement_expression(Syntax_Tree $tree, int $id): int
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed return or expression statement');
        if (($node->kind !== syntax_kind::return_statement) && ($node->kind !== syntax_kind::expression_statement)) {
            throw new \LogicException('Expected a parsed return or expression statement');
        }
        $child = $node->first_child_id;
        if (($child === 0) && ($node->kind === syntax_kind::return_statement)) {
            return 0;
        }
        $expression = Syntax_Access::expression($tree, $child);
        if ($expression->next_sibling_id !== 0) {
            throw new \LogicException('Expected one statement expression');
        }
        return $child;
    }

    /** Validate and expose condition/body/alternative roles from one parsed control statement. */
    public static function control_parts(Syntax_Tree $tree, int $id): control_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected control statement');
        if (!(($node->kind === syntax_kind::if_statement) || ($node->kind === syntax_kind::while_statement)
            || ($node->kind === syntax_kind::constexpr_if_statement) || ($node->kind === syntax_kind::consteval_if_statement))) {
            throw new \LogicException('Expected control statement');
        }
        $condition = $node->kind === syntax_kind::consteval_if_statement ? 0 : $node->first_child_id;
        $body = $condition === 0 ? $node->first_child_id : Syntax_Access::expression($tree, $condition)->next_sibling_id;
        $block = Syntax_Access::require_node($tree, $body, 'Expected control body block');
        if ($block->kind !== syntax_kind::block) {
            throw new \LogicException('Expected control body block');
        }
        $alternative = $block->next_sibling_id;
        if ($alternative !== 0)
        {
            $branch = Syntax_Access::require_node($tree, $alternative, 'Invalid alternative branch');
            $allowed = $branch->kind === syntax_kind::block;
            if ($node->kind !== syntax_kind::consteval_if_statement) {
                $allowed = $allowed || ($branch->kind === syntax_kind::if_statement)
                    || ($branch->kind === syntax_kind::constexpr_if_statement) || ($branch->kind === syntax_kind::consteval_if_statement);
            }
            if (($node->kind === syntax_kind::while_statement) || (!$allowed) || ($branch->next_sibling_id !== 0)) {
                throw new \LogicException('Invalid alternative branch');
            }
        }
        return new control_parts($condition, $body, $alternative);
    }

    /** Dense one-based node IDs; missing roles retain the caller's diagnostic. */
    private static function require_node(Syntax_Tree $tree, int $id, string $message): syntax_node
    {
        if (($id < 1) || ($id > count($tree->nodes))) {
            throw new \LogicException($message);
        }
        return $tree->nodes[$id - 1];
    }

    /** Optional role probe: absence is false, never an unchecked vector access. */
    private static function is_kind(Syntax_Tree $tree, int $id, syntax_kind $kind): bool
    {
        if (($id < 1) || ($id > count($tree->nodes))) {
            return false;
        }
        return $tree->nodes[$id - 1]->kind === $kind;
    }

    private static function leaf(Syntax_Tree $tree, int $id, syntax_kind $kind): syntax_node
    {
        $node = Syntax_Access::require_node($tree, $id, 'Invalid parsed name leaf');
        if (($node->kind !== $kind) || ($node->first_child_id !== 0)) {
            throw new \LogicException('Invalid parsed name leaf');
        }
        return $node;
    }

    /** Require a supported expression node and preserve its snapshot-local identity. */
    private static function expression(Syntax_Tree $tree, int $id): syntax_node
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed expression');
        if (!(($node->kind === syntax_kind::name) || ($node->kind === syntax_kind::template_application)
            || ($node->kind === syntax_kind::boolean_literal) || ($node->kind === syntax_kind::construct_expression)
            || ($node->kind === syntax_kind::field_expression) || ($node->kind === syntax_kind::index_expression)
            || ($node->kind === syntax_kind::integer_literal) || ($node->kind === syntax_kind::string_literal)
            || ($node->kind === syntax_kind::variable_name) || ($node->kind === syntax_kind::call_expression)
            || ($node->kind === syntax_kind::addition_expression) || ($node->kind === syntax_kind::less_than_expression))) {
            throw new \LogicException('Expected parsed expression');
        }
        if (!(($node->kind === syntax_kind::template_application) || ($node->kind === syntax_kind::construct_expression)
            || ($node->kind === syntax_kind::field_expression) || ($node->kind === syntax_kind::index_expression)
            || ($node->kind === syntax_kind::call_expression) || ($node->kind === syntax_kind::addition_expression)
            || ($node->kind === syntax_kind::less_than_expression))
            && ($node->first_child_id !== 0)) {
            throw new \LogicException('Invalid parsed expression leaf');
        }
        return $node;
    }

    /** Follow only field-selection bases to the root variable; reject temporary/compound destinations. */
    public static function place_root(Syntax_Tree $tree, int $id): int
    {
        while ((Syntax_Access::is_kind($tree, $id, syntax_kind::field_expression) || Syntax_Access::is_kind($tree, $id, syntax_kind::index_expression))) {
            $id = $tree->nodes[$id - 1]->first_child_id;
        }
        if (!Syntax_Access::is_kind($tree, $id, syntax_kind::variable_name)) {
            return 0;
        }
        return $id;
    }
}
