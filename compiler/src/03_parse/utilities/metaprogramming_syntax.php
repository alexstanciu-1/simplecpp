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
trait Metaprogramming_Syntax
{
    /** Return the ordinary declaration beneath template/evaluation wrappers, without changing their syntax. */
    public static function underlying_declaration(Syntax_Arena $tree, int $id): int
    {
        if (Syntax_Access::is_kind($tree, $id, \parse\SYNTAX_TEMPLATE_DECLARATION)) {
            $id = (int)Syntax_Access::template_parts($tree, $id)->declaration_id;
        }
        if (Syntax_Access::is_kind($tree, $id, \parse\SYNTAX_METHOD_DECLARATION)) {
            $id = (int)$tree->row($id)->first_child;
        }
        if ((Syntax_Access::is_kind($tree, $id, \parse\SYNTAX_CONSTEXPR_DECLARATION) || Syntax_Access::is_kind($tree, $id, \parse\SYNTAX_CONSTEVAL_DECLARATION))) {
            $id = Syntax_Access::evaluated_function($tree, $id);
        }
        return $id;
    }

    /** @compiler-api Validate the root of type syntax; nested arguments retain their unresolved expression form. */
    public static function type_syntax(Syntax_Arena $tree, int $id): Syntax_Row
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed type syntax');
        if ((int)$node->kind === \parse\SYNTAX_NAME) {
            return Syntax_Access::leaf($tree, $id, \parse\SYNTAX_NAME);
        }
        if ((int)$node->kind !== \parse\SYNTAX_TEMPLATE_APPLICATION) {
            throw new \LogicException('Expected parsed type syntax');
        }
        Syntax_Access::template_application_parts($tree, $id);
        return $node;
    }

    /** @compiler-api Read an application's name and first argument; siblings preserve argument order and arity. */
    public static function template_application_parts(Syntax_Arena $tree, int $id): template_application_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed template application');
        if ((int)$node->kind !== \parse\SYNTAX_TEMPLATE_APPLICATION) {
            throw new \LogicException('Expected parsed template application');
        }
        $name = Syntax_Access::leaf($tree, (int)$node->first_child, \parse\SYNTAX_NAME);
        if ((int)$name->next_sibling === 0) {
            throw new \LogicException('Expected nonempty template arguments');
        }
        Syntax_Access::expression($tree, (int)$name->next_sibling);
        $parts = new \parse\template_application_parts();
        $parts->name_id = (int)$node->first_child;
        $parts->first_argument_id = (int)$name->next_sibling;
        return $parts;
    }

    /** @compiler-api Expose the parameter list and wrapped declaration, preserving its ordinary source shape. */
    public static function template_parts(Syntax_Arena $tree, int $id): template_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed template declaration');
        if ((int)$node->kind !== \parse\SYNTAX_TEMPLATE_DECLARATION) {
            throw new \LogicException('Expected parsed template declaration');
        }
        $parameters = Syntax_Access::require_node($tree, (int)$node->first_child, 'Expected nonempty template parameter list');
        if (((int)$parameters->kind !== \parse\SYNTAX_TEMPLATE_PARAMETER_LIST) || ((int)$parameters->first_child === 0)) {
            throw new \LogicException('Expected nonempty template parameter list');
        }
        Syntax_Access::template_parameter_parts($tree, (int)$parameters->first_child);
        $declaration = Syntax_Access::require_node($tree, (int)$parameters->next_sibling, 'Invalid wrapped template declaration');
        if (((int)$declaration->next_sibling !== 0)
            || !(((int)$declaration->kind === \parse\SYNTAX_STRUCT_DECLARATION) || ((int)$declaration->kind === \parse\SYNTAX_FUNCTION_DECLARATION)
            || ((int)$declaration->kind === \parse\SYNTAX_CONSTEXPR_DECLARATION) || ((int)$declaration->kind === \parse\SYNTAX_CONSTEVAL_DECLARATION))) {
            throw new \LogicException('Invalid wrapped template declaration');
        }
        $parts = new \parse\template_parts();
        $parts->parameters_id = (int)$node->first_child;
        $parts->declaration_id = (int)$parameters->next_sibling;
        return $parts;
    }

    /** @compiler-api Distinguish type parameters from typed values without classifying their names semantically. */
    public static function template_parameter_parts(Syntax_Arena $tree, int $id): template_parameter_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed template parameter');
        if (!(((int)$node->kind === \parse\SYNTAX_TYPE_PARAMETER_DECLARATION) || ((int)$node->kind === \parse\SYNTAX_VALUE_PARAMETER_DECLARATION))) {
            throw new \LogicException('Expected parsed template parameter');
        }
        $name_id = (int)$node->first_child;
        $type_id = 0;
        if ((int)$node->kind === \parse\SYNTAX_VALUE_PARAMETER_DECLARATION) {
            $type_id = $name_id;
            $name_id = (int)Syntax_Access::type_syntax($tree, $type_id)->next_sibling;
        }
        $name = Syntax_Access::leaf($tree, $name_id, \parse\SYNTAX_NAME);
        if ((int)$name->next_sibling !== 0) {
            throw new \LogicException('Unexpected template parameter child');
        }
        $parts = new \parse\template_parameter_parts();
        $parts->name_id = $name_id;
        $parts->type_syntax_id = $type_id;
        return $parts;
    }

    /** @compiler-api Return the ordinary function wrapped by one constexpr/consteval declaration specifier. */
    public static function evaluated_function(Syntax_Arena $tree, int $id): int
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed function evaluation specifier');
        if (!(((int)$node->kind === \parse\SYNTAX_CONSTEXPR_DECLARATION) || ((int)$node->kind === \parse\SYNTAX_CONSTEVAL_DECLARATION))) {
            throw new \LogicException('Expected parsed function evaluation specifier');
        }
        Syntax_Access::function_parts($tree, (int)$node->first_child);
        if ((int)$tree->row((int)$node->first_child)->next_sibling !== 0) {
            throw new \LogicException('Unexpected function evaluation specifier child');
        }
        return (int)$node->first_child;
    }

    /** @compiler-api Read a constant initializer and optional type annotation without demanding its value. */
    public static function constant_parts(Syntax_Arena $tree, int $id): constant_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed constant declaration');
        if ((int)$node->kind !== \parse\SYNTAX_CONSTANT_DECLARATION) {
            throw new \LogicException('Expected parsed constant declaration');
        }
        $name = Syntax_Access::leaf($tree, (int)$node->first_child, \parse\SYNTAX_NAME);
        $initializer = (int)$name->next_sibling;
        $type_id = 0;
        if (Syntax_Access::is_kind($tree, $initializer, \parse\SYNTAX_TYPE_ANNOTATION))
        {
            $annotation = $tree->row($initializer);
            $type_id = (int)$annotation->first_child;
            if ((int)Syntax_Access::type_syntax($tree, $type_id)->next_sibling !== 0) {
                throw new \LogicException('Unexpected constant type child');
            }
            $initializer = (int)$annotation->next_sibling;
        }
        if ((int)Syntax_Access::expression($tree, $initializer)->next_sibling !== 0) {
            throw new \LogicException('Unexpected constant declaration child');
        }
        $parts = new \parse\constant_parts();
        $parts->name_id = (int)$node->first_child;
        $parts->type_syntax_id = $type_id;
        $parts->initializer_id = $initializer;
        return $parts;
    }
}
