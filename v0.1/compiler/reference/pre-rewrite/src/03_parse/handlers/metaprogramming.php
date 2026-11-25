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

use tokenize\token_kind;

/** @compiler-internal Declaration syntax only; parameter binding and evaluation belong to later owners. */
trait Metaprogramming_Parsing
{
    /** Wrap an ordinary declaration with ordered template parameters, without duplicating its body. */
    private function template_definition(): int
    {
        $node = $this->node(syntax_kind::template_declaration, $this->advance()->start);
        $last = 0;
        $this->child($node, $last, $this->template_parameters());
        if (!in_array($this->peek()->kind, [token_kind::struct_keyword, token_kind::function_keyword,
            token_kind::constexpr_keyword, token_kind::consteval_keyword], true)) {
            $this->fail('Expected struct or function declaration after template parameters');
        }
        $this->child($node, $last, $this->declaration(true));
        $this->finish($node);
        return $node;
    }

    /** Parse an explicit nonempty list of type or typed-value parameters; defaults and packs are deferred. */
    private function template_parameters(): int
    {
        $open = $this->expect(token_kind::left_angle, "'<' after template");
        $node = $this->node(syntax_kind::template_parameter_list, $open->start);
        $last = 0;
        do
        {
            $this->child($node, $last, $this->template_parameter());
            if ($this->peek()->kind !== token_kind::comma) {
                break;
            }
            $this->advance();
        }
        while (true);
        $this->expect(token_kind::right_angle, "'>' after template parameters; defaults and packs are unsupported");
        $this->finish($node);
        return $node;
    }

    /** Preserve parameter kind and spelling; a value parameter retains its unevaluated type syntax. */
    private function template_parameter(): int
    {
        $type_parameter = $this->peek()->kind === token_kind::typename_keyword;
        $node = $this->node($type_parameter ? syntax_kind::type_parameter_declaration : syntax_kind::value_parameter_declaration,
            $this->peek()->start);
        $last = 0;
        if ($type_parameter) {
            $this->advance();
        }
        else {
            $this->child($node, $last, $this->type_syntax());
        }
        $this->child($node, $last, $this->name('template parameter name'));
        $this->finish($node);
        return $node;
    }

    /** Keep the function evaluation specifier separate from the unchanged ordinary function shape. */
    private function evaluated_function(): int
    {
        $token = $this->advance();
        $node = $this->node($token->kind === token_kind::constexpr_keyword
            ? syntax_kind::constexpr_declaration : syntax_kind::consteval_declaration, $token->start);
        $last = 0;
        $this->child($node, $last, $this->function_definition());
        $this->finish($node);
        return $node;
    }

    /** Preserve a constant's name, optional explicit type and required initializer without computing a value. */
    private function constant_definition(): int
    {
        $node = $this->node(syntax_kind::constant_declaration, $this->advance()->start);
        $last = 0;
        $this->child($node, $last, $this->name('constant name'));
        if ($this->peek()->kind === token_kind::colon)
        {
            $annotation = $this->node(syntax_kind::type_annotation, $this->advance()->start);
            $type_tail = 0;
            $this->child($annotation, $type_tail, $this->type_syntax());
            $this->finish($annotation);
            $this->child($node, $last, $annotation);
        }
        $this->expect(token_kind::assignment, "'=' before constant initializer");
        $this->child($node, $last, $this->expression());
        return $this->finish_simple_statement($node);
    }
}
