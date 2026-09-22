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
    public static function function_parts(Syntax_Arena $tree, int $id): function_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed function declaration');
        if ((int)$node->kind !== \parse\SYNTAX_FUNCTION_DECLARATION) {
            throw new \LogicException('Expected a parsed function declaration');
        }
        $name = Syntax_Access::leaf($tree, (int)$node->first_child, \parse\SYNTAX_NAME);
        $parameters = Syntax_Access::require_node($tree, (int)$name->next_sibling, 'Invalid function parameter list');
        if ((int)$parameters->kind !== \parse\SYNTAX_PARAMETER_LIST) {
            throw new \LogicException('Invalid function parameter list');
        }
        $type = Syntax_Access::type_syntax($tree, (int)$parameters->next_sibling);
        $body = Syntax_Access::require_node($tree, (int)$type->next_sibling, 'Invalid function body or unexpected declaration child');
        if (((int)$body->kind !== \parse\SYNTAX_BLOCK) || ((int)$body->next_sibling !== 0)) {
            throw new \LogicException('Invalid function body or unexpected declaration child');
        }
        $parts = new \parse\function_parts();
        $parts->name_id = (int)$node->first_child;
        $parts->parameters_id = (int)$name->next_sibling;
        $parts->return_type_id = (int)$parameters->next_sibling;
        $parts->body_id = (int)$type->next_sibling;
        return $parts;
    }

    /** @compiler-api Read the struct name and first field; semantic eligibility belongs to resolution. */
    public static function struct_parts(Syntax_Arena $tree, int $id): struct_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed struct declaration');
        if ((int)$node->kind !== \parse\SYNTAX_STRUCT_DECLARATION) {
            throw new \LogicException('Expected a parsed struct declaration');
        }
        $name = Syntax_Access::leaf($tree, (int)$node->first_child, \parse\SYNTAX_NAME);
        $first = (int)$name->next_sibling;
        if (($first !== 0) && !((Syntax_Access::is_kind($tree, $first, \parse\SYNTAX_FIELD_DECLARATION))
            || (Syntax_Access::is_kind($tree, $first, \parse\SYNTAX_METHOD_DECLARATION)))) {
            throw new \LogicException('Invalid parsed struct field');
        }
        $parts = new \parse\struct_parts();
        $parts->name_id = (int)$node->first_child;
        $parts->first_member_id = $first;
        return $parts;
    }

    /** Return a lazy typed member traversal; validation starts on the first advance. */
    public static function struct_members(Syntax_Arena $tree, int $declaration, int $kind): Struct_Member_Cursor
    {
        return new Struct_Member_Cursor($tree, $declaration, $kind);
    }

    /** The method wrapper carries receiver access; its function subtree remains ordinary syntax. */
    public static function const_receiver(Syntax_Arena $tree, int $id): bool
    {
        $node = $tree->row($id);
        if ((int)$node->kind !== \parse\SYNTAX_METHOD_DECLARATION) {
            throw new \LogicException('Expected method declaration');
        }
        return (int)$tree->row((int)$node->first_child)->next_sibling !== 0;
    }

    /** @compiler-api Read a public field's type/name roles without interpreting its type or visibility. */
    public static function field_declaration_parts(Syntax_Arena $tree, int $id): field_declaration_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed field declaration');
        if ((int)$node->kind !== \parse\SYNTAX_FIELD_DECLARATION) {
            throw new \LogicException('Expected a parsed field declaration');
        }
        $type = Syntax_Access::type_syntax($tree, (int)$node->first_child);
        $variable_id = (int)$type->next_sibling;
        $variable = Syntax_Access::leaf($tree, $variable_id, \parse\SYNTAX_VARIABLE_NAME);
        $extent = (int)$variable->next_sibling;
        if ($extent !== 0) {
            if ((int)Syntax_Access::expression($tree, $extent)->next_sibling !== 0) {
                throw new \LogicException('Unexpected field declaration child');
            }
        }
        $parts = new \parse\field_declaration_parts();
        $parts->type_syntax_id = (int)$node->first_child;
        $parts->variable_id = $variable_id;
        $parts->extent_id = $extent;
        return $parts;
    }

    /** @compiler-api Read the callee syntax node ID of a call expression. */
    public static function call_target(Syntax_Arena $tree, int $id): int
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed call');
        if ((int)$node->kind !== \parse\SYNTAX_CALL_EXPRESSION) {
            throw new \LogicException('Expected a parsed call');
        }
        if (!Syntax_Access::is_kind($tree, (int)$node->first_child, \parse\SYNTAX_FIELD_EXPRESSION)) {
            Syntax_Access::type_syntax($tree, (int)$node->first_child);
        }
        return (int)$node->first_child;
    }

    // Arguments are the target's following siblings, in source order. No
    // extra list node or copied argument index is needed, including empty calls.
    /** @compiler-api Read the first argument expression ID or zero; follow siblings for source order. */
    public static function first_argument(Syntax_Arena $tree, int $id): int
    {
        $target = Syntax_Access::call_target($tree, $id);
        $first = (int)$tree->row($target)->next_sibling;
        if ($first !== 0) {
            Syntax_Access::expression($tree, $first);
        }
        return $first;
    }

    /** @compiler-api Read the first parameter declaration ID or zero from a parameter-list node. */
    public static function first_parameter(Syntax_Arena $tree, int $list_id): int
    {
        $list = Syntax_Access::require_node($tree, $list_id, 'Expected parsed parameter list');
        if ((int)$list->kind !== \parse\SYNTAX_PARAMETER_LIST) {
            throw new \LogicException('Expected parsed parameter list');
        }
        $first = (int)$list->first_child;
        if (($first !== 0) && (!Syntax_Access::is_kind($tree, $first, \parse\SYNTAX_PARAMETER_DECLARATION))) {
            throw new \LogicException('Invalid parsed parameter list child');
        }
        return $first;
    }

    /** @compiler-api Read parameter variable/type-syntax child roles; no name/type interpretation. */
    public static function parameter_parts(Syntax_Arena $tree, int $id): parameter_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed parameter declaration');
        if ((int)$node->kind !== \parse\SYNTAX_PARAMETER_DECLARATION) {
            throw new \LogicException('Expected parsed parameter declaration');
        }
        $variable_id = (int)$node->first_child;
        $variable = Syntax_Access::leaf($tree, $variable_id, \parse\SYNTAX_VARIABLE_NAME);
        $type_id = (int)$variable->next_sibling;
        $type = Syntax_Access::type_syntax($tree, $type_id);
        if ((int)$type->next_sibling !== 0)
        {
            $modifier = Syntax_Access::require_node($tree, (int)$type->next_sibling, 'Unexpected parameter declaration child');
            if (((int)$modifier->first_child !== 0) || ((int)$modifier->next_sibling !== 0)
                || !(((int)$modifier->kind === \parse\SYNTAX_REFERENCE_ANNOTATION) || ((int)$modifier->kind === \parse\SYNTAX_CONST_REFERENCE_ANNOTATION))) {
                throw new \LogicException('Unexpected parameter declaration child');
            }
            $parts = new \parse\parameter_parts();
        $parts->variable_id = $variable_id;
        $parts->type_syntax_id = $type_id;
        $parts->reference = (int)$modifier->kind;
        return $parts;
        }
        $parts = new \parse\parameter_parts();
        $parts->variable_id = $variable_id;
        $parts->type_syntax_id = $type_id;
        $parts->reference = 0;
        return $parts;
    }

    /** @compiler-api Read variable/type-syntax/initializer roles of a local declaration. */
    public static function local_declaration_parts(Syntax_Arena $tree, int $id): local_declaration_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed local declaration');
        if ((int)$node->kind !== \parse\SYNTAX_LOCAL_DECLARATION) {
            throw new \LogicException('Expected a parsed local declaration');
        }
        $variable_id = (int)$node->first_child;
        $variable = Syntax_Access::leaf($tree, $variable_id, \parse\SYNTAX_VARIABLE_NAME);
        $type_id = (int)$variable->next_sibling;
        $type = Syntax_Access::type_syntax($tree, $type_id);
        $initializer_id = (int)$type->next_sibling;
        if ($initializer_id !== 0) {
            if ((int)Syntax_Access::expression($tree, $initializer_id)->next_sibling !== 0) {
                throw new \LogicException('Unexpected local declaration child');
            }
        }
        $parts = new \parse\local_declaration_parts();
        $parts->variable_id = $variable_id;
        $parts->type_syntax_id = $type_id;
        $parts->initializer_id = $initializer_id;
        return $parts;
    }

    /** @compiler-api Read target/value roles of an assignment. */
    public static function assignment_parts(Syntax_Arena $tree, int $id): assignment_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed assignment');
        if ((int)$node->kind !== \parse\SYNTAX_ASSIGNMENT_STATEMENT) {
            throw new \LogicException('Expected a parsed assignment');
        }
        $target_id = (int)$node->first_child;
        $target = Syntax_Access::expression($tree, $target_id);
        $value_id = (int)$target->next_sibling;
        $value = Syntax_Access::expression($tree, $value_id);
        if ((int)$value->next_sibling !== 0) {
            throw new \LogicException('Unexpected assignment child');
        }
        $parts = new \parse\assignment_parts();
        $parts->target_id = $target_id;
        $parts->value_id = $value_id;
        return $parts;
    }

    // Zero means a bare return; expression statements always have one child.
    /** @compiler-api Read the expression ID; zero denotes a bare return, not an absent expression statement. */
    public static function statement_expression(Syntax_Arena $tree, int $id): int
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected a parsed return or expression statement');
        if (((int)$node->kind !== \parse\SYNTAX_RETURN_STATEMENT) && ((int)$node->kind !== \parse\SYNTAX_EXPRESSION_STATEMENT)) {
            throw new \LogicException('Expected a parsed return or expression statement');
        }
        $child = (int)$node->first_child;
        if (($child === 0) && ((int)$node->kind === \parse\SYNTAX_RETURN_STATEMENT)) {
            return 0;
        }
        $expression = Syntax_Access::expression($tree, $child);
        if ((int)$expression->next_sibling !== 0) {
            throw new \LogicException('Expected one statement expression');
        }
        return $child;
    }

    /** Validate and expose condition/body/alternative roles from one parsed control statement. */
    public static function control_parts(Syntax_Arena $tree, int $id): control_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected control statement');
        if (!(((int)$node->kind === \parse\SYNTAX_IF_STATEMENT) || ((int)$node->kind === \parse\SYNTAX_WHILE_STATEMENT)
            || ((int)$node->kind === \parse\SYNTAX_CONSTEXPR_IF_STATEMENT) || ((int)$node->kind === \parse\SYNTAX_CONSTEVAL_IF_STATEMENT))) {
            throw new \LogicException('Expected control statement');
        }
        $condition = (int)$node->kind === \parse\SYNTAX_CONSTEVAL_IF_STATEMENT ? 0 : (int)$node->first_child;
        $body = $condition === 0 ? (int)$node->first_child : (int)Syntax_Access::expression($tree, $condition)->next_sibling;
        $block = Syntax_Access::require_node($tree, $body, 'Expected control body block');
        if ((int)$block->kind !== \parse\SYNTAX_BLOCK) {
            throw new \LogicException('Expected control body block');
        }
        $alternative = (int)$block->next_sibling;
        if ($alternative !== 0)
        {
            $branch = Syntax_Access::require_node($tree, $alternative, 'Invalid alternative branch');
            $allowed = (int)$branch->kind === \parse\SYNTAX_BLOCK;
            if ((int)$node->kind !== \parse\SYNTAX_CONSTEVAL_IF_STATEMENT) {
                $allowed = $allowed || ((int)$branch->kind === \parse\SYNTAX_IF_STATEMENT)
                    || ((int)$branch->kind === \parse\SYNTAX_CONSTEXPR_IF_STATEMENT) || ((int)$branch->kind === \parse\SYNTAX_CONSTEVAL_IF_STATEMENT);
            }
            if (((int)$node->kind === \parse\SYNTAX_WHILE_STATEMENT) || (!$allowed) || ((int)$branch->next_sibling !== 0)) {
                throw new \LogicException('Invalid alternative branch');
            }
        }
        $parts = new \parse\control_parts();
        $parts->condition = $condition;
        $parts->body = $body;
        $parts->alternative = $alternative;
        return $parts;
    }

    /** Dense one-based node IDs; missing roles retain the caller's diagnostic. */
    private static function require_node(Syntax_Arena $tree, int $id, string $message): Syntax_Row
    {
        if (($id < 1) || ($id > $tree->size())) {
            throw new \LogicException($message);
        }
        return $tree->row($id);
    }

    /** Optional role probe: absence is false, never an unchecked vector access. */
    private static function is_kind(Syntax_Arena $tree, int $id, int $kind): bool
    {
        if (($id < 1) || ($id > $tree->size())) {
            return false;
        }
        return (int)$tree->row($id)->kind === $kind;
    }

    private static function leaf(Syntax_Arena $tree, int $id, int $kind): Syntax_Row
    {
        $node = Syntax_Access::require_node($tree, $id, 'Invalid parsed name leaf');
        if (((int)$node->kind !== $kind) || ((int)$node->first_child !== 0)) {
            throw new \LogicException('Invalid parsed name leaf');
        }
        return $node;
    }

    /** Require a supported expression node and preserve its snapshot-local identity. */
    private static function expression(Syntax_Arena $tree, int $id): Syntax_Row
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed expression');
        if (!(((int)$node->kind === \parse\SYNTAX_NAME) || ((int)$node->kind === \parse\SYNTAX_TEMPLATE_APPLICATION)
            || ((int)$node->kind === \parse\SYNTAX_BOOLEAN_LITERAL) || ((int)$node->kind === \parse\SYNTAX_CONSTRUCT_EXPRESSION)
            || ((int)$node->kind === \parse\SYNTAX_FIELD_EXPRESSION) || ((int)$node->kind === \parse\SYNTAX_INDEX_EXPRESSION)
            || ((int)$node->kind === \parse\SYNTAX_INTEGER_LITERAL) || ((int)$node->kind === \parse\SYNTAX_STRING_LITERAL)
            || ((int)$node->kind === \parse\SYNTAX_VARIABLE_NAME) || ((int)$node->kind === \parse\SYNTAX_CALL_EXPRESSION)
            || ((int)$node->kind === \parse\SYNTAX_ADDITION_EXPRESSION) || ((int)$node->kind === \parse\SYNTAX_LESS_THAN_EXPRESSION))) {
            throw new \LogicException('Expected parsed expression');
        }
        if (!(((int)$node->kind === \parse\SYNTAX_TEMPLATE_APPLICATION) || ((int)$node->kind === \parse\SYNTAX_CONSTRUCT_EXPRESSION)
            || ((int)$node->kind === \parse\SYNTAX_FIELD_EXPRESSION) || ((int)$node->kind === \parse\SYNTAX_INDEX_EXPRESSION)
            || ((int)$node->kind === \parse\SYNTAX_CALL_EXPRESSION) || ((int)$node->kind === \parse\SYNTAX_ADDITION_EXPRESSION)
            || ((int)$node->kind === \parse\SYNTAX_LESS_THAN_EXPRESSION))
            && ((int)$node->first_child !== 0)) {
            throw new \LogicException('Invalid parsed expression leaf');
        }
        return $node;
    }

    /** Follow only field-selection bases to the root variable; reject temporary/compound destinations. */
    public static function place_root(Syntax_Arena $tree, int $id): int
    {
        while ((Syntax_Access::is_kind($tree, $id, \parse\SYNTAX_FIELD_EXPRESSION) || Syntax_Access::is_kind($tree, $id, \parse\SYNTAX_INDEX_EXPRESSION))) {
            $id = (int)$tree->row($id)->first_child;
        }
        if (!Syntax_Access::is_kind($tree, $id, \parse\SYNTAX_VARIABLE_NAME)) {
            return 0;
        }
        return $id;
    }
}
