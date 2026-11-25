<?php
declare(strict_types=1);

/*
 * Role: Preserve template declarations, evaluation specifiers and constant declarations.
 * Used by: File_Parser (private trait methods on this owner)
 * Call map:
 *   template_definition() -> template_parameters(); declaration()
 *   evaluated_function() / constant_definition() -> ordinary declaration/expression parsers
 */

namespace parse;

/** @compiler-internal Declaration syntax only; parameter binding and evaluation belong to later owners. */
trait Metaprogramming_Parsing
{
    /** Wrap an ordinary declaration with ordered template parameters, without duplicating its body. */
    private function template_definition(): int
    {
        $node = $this->node(\parse\SYNTAX_TEMPLATE_DECLARATION, (int)$this->advance()->start, 0);

        $this->result->tree->child($node, $this->template_parameters());
        if (!(($this->kind() === \tokenize\TOKEN_STRUCT_KEYWORD) || ($this->kind() === \tokenize\TOKEN_FUNCTION_KEYWORD) || ($this->kind() === \tokenize\TOKEN_CONSTEXPR_KEYWORD) || ($this->kind() === \tokenize\TOKEN_CONSTEVAL_KEYWORD))) {
            $this->fail('Expected struct or function declaration after template parameters');
        }
        $this->result->tree->child($node, $this->declaration(true));
        $this->finish($node);
        return $node;
    }

    /** Parse an explicit nonempty list of type or typed-value parameters; defaults and packs are deferred. */
    private function template_parameters(): int
    {
        $open = $this->expect(\tokenize\TOKEN_LEFT_ANGLE, "'<' after template");
        $node = $this->node(\parse\SYNTAX_TEMPLATE_PARAMETER_LIST, (int)$open->start, 0);

        while (true)
        {
            $this->result->tree->child($node, $this->template_parameter());
            if ($this->kind() !== \tokenize\TOKEN_COMMA) {
                break;
            }
            $this->advance();
        }
        $this->expect(\tokenize\TOKEN_RIGHT_ANGLE, "'>' after template parameters; defaults and packs are unsupported");
        $this->finish($node);
        return $node;
    }

    /** Preserve parameter kind and spelling; a value parameter retains its unevaluated type syntax. */
    private function template_parameter(): int
    {
        $type_parameter = $this->kind() === \tokenize\TOKEN_TYPENAME_KEYWORD;
        $node = $this->node($type_parameter ? \parse\SYNTAX_TYPE_PARAMETER_DECLARATION : \parse\SYNTAX_VALUE_PARAMETER_DECLARATION,
            (int)$this->peek()->start, 0);

        if ($type_parameter) {
            $this->advance();
        }
        else {
            $this->result->tree->child($node, $this->expression(\parse\EXPR_TYPE));
        }
        $this->result->tree->child($node, $this->name());
        $this->finish($node);
        return $node;
    }

    /** Keep the function evaluation specifier separate from the unchanged ordinary function shape. */
    private function evaluated_function(): int
    {
        $token = $this->advance();
        $node = $this->node((int)$token->kind === \tokenize\TOKEN_CONSTEXPR_KEYWORD
            ? \parse\SYNTAX_CONSTEXPR_DECLARATION : \parse\SYNTAX_CONSTEVAL_DECLARATION, (int)$token->start, 0);

        $this->result->tree->child($node, $this->function_definition());
        $this->finish($node);
        return $node;
    }

    /** Preserve a constant's name, optional explicit type and required initializer without computing a value. */
    private function constant_definition(): int
    {
        $node = $this->node(\parse\SYNTAX_CONSTANT_DECLARATION, (int)$this->advance()->start, 0);

        $this->result->tree->child($node, $this->name());
        if ($this->kind() === \tokenize\TOKEN_COLON)
        {
            $annotation = $this->node(\parse\SYNTAX_TYPE_ANNOTATION, (int)$this->advance()->start, 0);

            $this->result->tree->child($annotation, $this->expression(\parse\EXPR_TYPE));
            $this->finish($annotation);
            $this->result->tree->child($node, $annotation);
        }
        $this->expect(\tokenize\TOKEN_ASSIGNMENT, "'=' before constant initializer");
        $this->result->tree->child($node, $this->expression(\parse\EXPR_VALUE));
        return $this->finish_simple_statement($node);
    }
}
