<?php
declare(strict_types=1);

/*
 * Role: Expose template and constant syntax roles without binding or evaluation.
 * Used by: Syntax_Access (static structural query methods composed on this owner)
 * Call map:
 *   template_parts(); template_parameter_parts(); template_application_parts(); constant_parts()
 *     -> type_syntax(); leaf(); expression() [shared structural validation]
 */

namespace parse;

/** @compiler-internal Structural accessors composed by Syntax_Access; inputs remain immutable. */
trait Original_Metaprogramming_Syntax
{
    /** Return the ordinary declaration beneath template/evaluation wrappers, without changing their syntax. */
    public static function underlying_declaration(Syntax_Tree $tree, int $id): int
    {
        if (($tree->nodes[$id - 1]->kind ?? null) === syntax_kind::template_declaration) {
            $id = self::template_parts($tree, $id)->declaration_id;
        }
        if (($tree->nodes[$id - 1]->kind ?? null) === syntax_kind::method_declaration) {
            $id = $tree->nodes[$id - 1]->first_child_id;
        }
        if (in_array($tree->nodes[$id - 1]->kind ?? null,
            [syntax_kind::constexpr_declaration, syntax_kind::consteval_declaration], true)) {
            $id = self::evaluated_function($tree, $id);
        }
        return $id;
    }

    /** @compiler-api Validate the root of type syntax; nested arguments retain their unresolved expression form. */
    public static function type_syntax(Syntax_Tree $tree, int $id): syntax_node
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind === syntax_kind::name) {
            return self::leaf($tree, $id, syntax_kind::name);
        }
        if ($node?->kind !== syntax_kind::template_application) {
            throw new \LogicException('Expected parsed type syntax');
        }
        self::template_application_parts($tree, $id);
        return $node;
    }

    /** @compiler-api Read an application's name and first argument; siblings preserve argument order and arity. */
    public static function template_application_parts(Syntax_Tree $tree, int $id): template_application_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::template_application) {
            throw new \LogicException('Expected parsed template application');
        }
        $name = self::leaf($tree, $node->first_child_id, syntax_kind::name);
        if ($name->next_sibling_id === 0) {
            throw new \LogicException('Expected nonempty template arguments');
        }
        self::expression($tree, $name->next_sibling_id);
        return new template_application_parts($node->first_child_id, $name->next_sibling_id);
    }

    /** @compiler-api Expose the parameter list and wrapped declaration, preserving its ordinary source shape. */
    public static function template_parts(Syntax_Tree $tree, int $id): template_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::template_declaration) {
            throw new \LogicException('Expected parsed template declaration');
        }
        $parameters = $tree->nodes[$node->first_child_id - 1] ?? null;
        if (($parameters?->kind !== syntax_kind::template_parameter_list) || ($parameters->first_child_id === 0)) {
            throw new \LogicException('Expected nonempty template parameter list');
        }
        self::template_parameter_parts($tree, $parameters->first_child_id);
        $declaration = $tree->nodes[$parameters->next_sibling_id - 1] ?? null;
        if (($declaration === null) || ($declaration->next_sibling_id !== 0)
            || !in_array($declaration->kind, [syntax_kind::struct_declaration, syntax_kind::function_declaration,
                syntax_kind::constexpr_declaration, syntax_kind::consteval_declaration], true)) {
            throw new \LogicException('Invalid wrapped template declaration');
        }
        return new template_parts($node->first_child_id, $parameters->next_sibling_id);
    }

    /** @compiler-api Distinguish type parameters from typed values without classifying their names semantically. */
    public static function template_parameter_parts(Syntax_Tree $tree, int $id): template_parameter_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if (($node === null) || !in_array($node->kind,
            [syntax_kind::type_parameter_declaration, syntax_kind::value_parameter_declaration], true)) {
            throw new \LogicException('Expected parsed template parameter');
        }
        $name_id = $node->first_child_id;
        $type_id = 0;
        if ($node->kind === syntax_kind::value_parameter_declaration) {
            $type_id = $name_id;
            $name_id = self::type_syntax($tree, $type_id)->next_sibling_id;
        }
        $name = self::leaf($tree, $name_id, syntax_kind::name);
        if ($name->next_sibling_id !== 0) {
            throw new \LogicException('Unexpected template parameter child');
        }
        return new template_parameter_parts($name_id, $type_id);
    }

    /** @compiler-api Return the ordinary function wrapped by one constexpr/consteval declaration specifier. */
    public static function evaluated_function(Syntax_Tree $tree, int $id): int
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if (($node === null) || !in_array($node->kind,
            [syntax_kind::constexpr_declaration, syntax_kind::consteval_declaration], true)) {
            throw new \LogicException('Expected parsed function evaluation specifier');
        }
        self::function_parts($tree, $node->first_child_id);
        if ($tree->nodes[$node->first_child_id - 1]->next_sibling_id !== 0) {
            throw new \LogicException('Unexpected function evaluation specifier child');
        }
        return $node->first_child_id;
    }

    /** @compiler-api Read a constant initializer and optional type annotation without demanding its value. */
    public static function constant_parts(Syntax_Tree $tree, int $id): constant_parts
    {
        $node = $tree->nodes[$id - 1] ?? null;
        if ($node?->kind !== syntax_kind::constant_declaration) {
            throw new \LogicException('Expected parsed constant declaration');
        }
        $name = self::leaf($tree, $node->first_child_id, syntax_kind::name);
        $initializer = $name->next_sibling_id;
        $type_id = 0;
        $annotation = $tree->nodes[$initializer - 1] ?? null;
        if ($annotation?->kind === syntax_kind::type_annotation)
        {
            $type_id = $annotation->first_child_id;
            if (self::type_syntax($tree, $type_id)->next_sibling_id !== 0) {
                throw new \LogicException('Unexpected constant type child');
            }
            $initializer = $annotation->next_sibling_id;
        }
        if (self::expression($tree, $initializer)->next_sibling_id !== 0) {
            throw new \LogicException('Unexpected constant declaration child');
        }
        return new constant_parts($node->first_child_id, $type_id, $initializer);
    }
}
