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
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::function_declaration) {
            throw new \LogicException('Expected a parsed function declaration');
        }
        $name = self::leaf($tree, $node->first_child_id, syntax_kind::name);
        $parameters = $tree->nodes[$name->next_sibling_id - 1] ?? null;
        if ($parameters?->kind !== syntax_kind::parameter_list) {
            throw new \LogicException('Invalid function parameter list');
        }
        $type = self::type_syntax($tree, $parameters->next_sibling_id);
        $body = $tree->nodes[$type->next_sibling_id - 1] ?? null;
        if (($body?->kind !== syntax_kind::block) || ($body->next_sibling_id !== 0)) {
            throw new \LogicException('Invalid function body or unexpected declaration child');
        }
        return new function_parts($node->first_child_id, $name->next_sibling_id,
            $parameters->next_sibling_id, $type->next_sibling_id);
    }

    /** @compiler-api Read the struct name and first field; semantic eligibility belongs to resolution. */
    public static function struct_parts(Syntax_Tree $tree, int $id): struct_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::struct_declaration) {
            throw new \LogicException('Expected a parsed struct declaration');
        }
        $name = self::leaf($tree, $node->first_child_id, syntax_kind::name);
        $first = $name->next_sibling_id;
        if (($first !== 0) && !in_array(($tree->nodes[$first - 1] ?? null)?->kind, [syntax_kind::field_declaration, syntax_kind::method_declaration], true)) {
            throw new \LogicException('Invalid parsed struct field');
        }
        return new struct_parts($node->first_child_id, $first);
    }

    /** Enumerate members of one role without exposing consumers to mixed sibling-list traversal. */
    public static function struct_members(Syntax_Tree $tree, int $declaration, syntax_kind $kind): \Generator
    {
        $parts = self::struct_parts($tree, self::underlying_declaration($tree, $declaration));
        for ($id = $parts->first_member_id; $id !== 0; $id = $tree->nodes[$id - 1]->next_sibling_id) {
            if ($tree->nodes[$id - 1]->kind === $kind) {
                yield $id;
            }
        }
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
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::field_declaration) {
            throw new \LogicException('Expected a parsed field declaration');
        }
        $type = self::type_syntax($tree, $node->first_child_id);
        $variable_id = $type->next_sibling_id;
        $variable = self::leaf($tree, $variable_id, syntax_kind::variable_name);
        $extent = $variable->next_sibling_id;
        if (($extent !== 0) && (self::expression($tree, $extent)->next_sibling_id !== 0)) {
            throw new \LogicException('Unexpected field declaration child');
        }
        return new field_declaration_parts($node->first_child_id, $variable_id, $extent);
    }

    /** @compiler-api Read the callee syntax node ID of a call expression. */
    public static function call_target(Syntax_Tree $tree, int $id): int
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::call_expression) {
            throw new \LogicException('Expected a parsed call');
        }
        if (($tree->nodes[$node->first_child_id - 1]->kind ?? null) !== syntax_kind::field_expression) {
            self::type_syntax($tree, $node->first_child_id);
        }
        return $node->first_child_id;
    }

    // Arguments are the target's following siblings, in source order. No
    // extra list node or copied argument index is needed, including empty calls.
    /** @compiler-api Read the first argument expression ID or zero; follow siblings for source order. */
    public static function first_argument(Syntax_Tree $tree, int $id): int
    {
        $target = self::call_target($tree, $id);
        $first = $tree->nodes[$target - 1]->next_sibling_id;
        if ($first !== 0) {
            self::expression($tree, $first);
        }
        return $first;
    }

    /** @compiler-api Read the first parameter declaration ID or zero from a parameter-list node. */
    public static function first_parameter(Syntax_Tree $tree, int $list_id): int
    {
        $list = $tree->nodes[$list_id - 1] ?? null;
        if ($list?->kind !== syntax_kind::parameter_list) {
            throw new \LogicException('Expected parsed parameter list');
        }
        $first = $list->first_child_id;
        if (($first !== 0) && (($tree->nodes[$first - 1] ?? null)?->kind !== syntax_kind::parameter_declaration)) {
            throw new \LogicException('Invalid parsed parameter list child');
        }
        return $first;
    }

    /** @compiler-api Read parameter variable/type-syntax child roles; no name/type interpretation. */
    public static function parameter_parts(Syntax_Tree $tree, int $id): parameter_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::parameter_declaration) {
            throw new \LogicException('Expected parsed parameter declaration');
        }
        $variable_id = $node->first_child_id;
        $variable = self::leaf($tree, $variable_id, syntax_kind::variable_name);
        $type_id = $variable->next_sibling_id;
        $type = self::type_syntax($tree, $type_id);
        $reference = null;
        if ($type->next_sibling_id !== 0)
        {
            $modifier = $tree->nodes[$type->next_sibling_id - 1] ?? null;
            if (($modifier === null) || ($modifier->first_child_id !== 0) || ($modifier->next_sibling_id !== 0)
                || !in_array($modifier->kind, [syntax_kind::reference_annotation, syntax_kind::const_reference_annotation], true)) {
                throw new \LogicException('Unexpected parameter declaration child');
            }
            $reference = $modifier->kind;
        }
        return new parameter_parts($variable_id, $type_id, $reference);
    }

    /** @compiler-api Read variable/type-syntax/initializer roles of a local declaration. */
    public static function local_declaration_parts(Syntax_Tree $tree, int $id): local_declaration_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::local_declaration) {
            throw new \LogicException('Expected a parsed local declaration');
        }
        $variable_id = $node->first_child_id;
        $variable = self::leaf($tree, $variable_id, syntax_kind::variable_name);
        $type_id = $variable->next_sibling_id;
        $type = self::type_syntax($tree, $type_id);
        $initializer_id = $type->next_sibling_id;
        $initializer = $initializer_id === 0 ? null : self::expression($tree, $initializer_id);
        if (($initializer !== null) && ($initializer->next_sibling_id !== 0)) {
            throw new \LogicException('Unexpected local declaration child');
        }
        return new local_declaration_parts($variable_id, $type_id, $initializer_id);
    }

    /** @compiler-api Read target/value roles of an assignment. */
    public static function assignment_parts(Syntax_Tree $tree, int $id): assignment_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::assignment_statement) {
            throw new \LogicException('Expected a parsed assignment');
        }
        $target_id = $node->first_child_id;
        $target = self::expression($tree, $target_id);
        $value_id = $target->next_sibling_id;
        $value = self::expression($tree, $value_id);
        if ($value->next_sibling_id !== 0) {
            throw new \LogicException('Unexpected assignment child');
        }
        return new assignment_parts($target_id, $value_id);
    }

    // Zero means a bare return; expression statements always have one child.
    /** @compiler-api Read the expression ID; zero denotes a bare return, not an absent expression statement. */
    public static function statement_expression(Syntax_Tree $tree, int $id): int
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if (($node?->kind !== syntax_kind::return_statement) && ($node?->kind !== syntax_kind::expression_statement)) {
            throw new \LogicException('Expected a parsed return or expression statement');
        }
        $child = $node->first_child_id;
        if (($child === 0) && ($node->kind === syntax_kind::return_statement)) {
            return 0;
        }
        $expression = self::expression($tree, $child);
        if ($expression->next_sibling_id !== 0) {
            throw new \LogicException('Expected one statement expression');
        }
        return $child;
    }

    /** Validate and expose condition/body/alternative roles from one parsed control statement. */
    public static function control_parts(Syntax_Tree $tree, int $id): control_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if (($node === null) || !in_array($node->kind, [syntax_kind::if_statement, syntax_kind::while_statement,
            syntax_kind::constexpr_if_statement, syntax_kind::consteval_if_statement], true)) {
            throw new \LogicException('Expected control statement');
        }
        $condition = $node->kind === syntax_kind::consteval_if_statement ? 0 : $node->first_child_id;
        $body = $condition === 0 ? $node->first_child_id : self::expression($tree, $condition)->next_sibling_id;
        $block = $tree->nodes[$body - 1] ?? null;
        if ($block?->kind !== syntax_kind::block) {
            throw new \LogicException('Expected control body block');
        }
        $alternative = $block->next_sibling_id;
        if ($alternative !== 0)
        {
            $allowed = [syntax_kind::block];
            if ($node->kind !== syntax_kind::consteval_if_statement) {
                $allowed = [...$allowed, syntax_kind::if_statement, syntax_kind::constexpr_if_statement, syntax_kind::consteval_if_statement];
            }
            $branch = $tree->nodes[$alternative - 1] ?? null;
            if (($node->kind === syntax_kind::while_statement) || ($branch === null)
                || !in_array($branch->kind, $allowed, true) || ($branch->next_sibling_id !== 0)) {
                throw new \LogicException('Invalid alternative branch');
            }
        }
        return new control_parts($condition, $body, $alternative);
    }

    private static function leaf(Syntax_Tree $tree, int $id, syntax_kind $kind): syntax_node
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if (($node?->kind !== $kind) || ($node->first_child_id !== 0)) {
            throw new \LogicException('Invalid parsed name leaf');
        }
        return $node;
    }

    /** Require a supported expression node and preserve its snapshot-local identity. */
    private static function expression(Syntax_Tree $tree, int $id): syntax_node
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if (($node === null) || !in_array($node->kind,
            [syntax_kind::name, syntax_kind::template_application, syntax_kind::boolean_literal,
                syntax_kind::construct_expression, syntax_kind::field_expression, syntax_kind::index_expression, syntax_kind::integer_literal,
                syntax_kind::string_literal, syntax_kind::variable_name, syntax_kind::call_expression,
                syntax_kind::addition_expression, syntax_kind::less_than_expression], true)) {
            throw new \LogicException('Expected parsed expression');
        }
        if (!in_array($node->kind, [syntax_kind::template_application, syntax_kind::construct_expression,
            syntax_kind::field_expression, syntax_kind::index_expression, syntax_kind::call_expression, syntax_kind::addition_expression, syntax_kind::less_than_expression], true)
            && ($node->first_child_id !== 0)) {
            throw new \LogicException('Invalid parsed expression leaf');
        }
        return $node;
    }

    /** Follow only field-selection bases to the root variable; reject temporary/compound destinations. */
    public static function place_root(Syntax_Tree $tree, int $id): int
    {
        while (in_array($tree->nodes[$id - 1]->kind ?? null, [syntax_kind::field_expression, syntax_kind::index_expression], true)) {
            $id = $tree->nodes[$id - 1]->first_child_id;
        }
        if (($tree->nodes[$id - 1]->kind ?? null) !== syntax_kind::variable_name) {
            return 0;
        }
        return $id;
    }
}
