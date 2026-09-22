<?php
declare(strict_types=1);

/*
 * Role: Dispatch declarations and parse ordinary function/struct contracts.
 * Used by: File_Parser (private trait methods on this owner)
 * Call map:
 *   declaration()
 *     -> function_definition() / struct_definition()
 *     -> template_definition() / evaluated_function() / constant_definition()
 */

namespace parse;

/**
 * @compiler-internal File_Parser declaration recognition and complete handlers.
 * A zero declaration consumes no tokens and allocates no nodes. Recognized
 * declarations validate their context before parsing; the statement list owns
 * registration in definitions. State and token/tree helpers belong to File_Parser.
 */
trait Declaration_Parsing
{
    /** Recognize a file-level declaration without consuming ordinary statement tokens. */
    private function declaration(bool $file_scope): int
    {
        $kind = $this->kind();
        if (($kind === \tokenize\TOKEN_CONST_KEYWORD) && ($file_scope)) {
            return $this->constant_definition();
        }
        if (!(($kind === \tokenize\TOKEN_FUNCTION_KEYWORD) || ($kind === \tokenize\TOKEN_STRUCT_KEYWORD) || ($kind === \tokenize\TOKEN_TEMPLATE_KEYWORD) || ($kind === \tokenize\TOKEN_CONSTEXPR_KEYWORD) || ($kind === \tokenize\TOKEN_CONSTEVAL_KEYWORD))) {
            return 0;
        }
        if (!$file_scope) {
            $this->fail('Nested function definitions and struct/template declarations are unsupported');
        }
        if ($kind === \tokenize\TOKEN_FUNCTION_KEYWORD) { return $this->function_definition(); }
        if ($kind === \tokenize\TOKEN_STRUCT_KEYWORD) { return $this->struct_definition(); }
        if ($kind === \tokenize\TOKEN_TEMPLATE_KEYWORD) { return $this->template_definition(); }
        return $this->evaluated_function();
    }

    /** Attach the function name, parameter/result contract and separately parsed body. */
    private function function_definition(): int
    {
        // Create the declaration and attach its name before its contract and body.
        $start = (int)$this->expect(\tokenize\TOKEN_FUNCTION_KEYWORD, "'function'")->start;
        $function = $this->node(\parse\SYNTAX_FUNCTION_DECLARATION, $start, 0);

        $name = $this->name();
        $this->result->tree->child($function, $name);

        $parameters = $this->parameter_list();
        $this->result->tree->child($function, $parameters);

        // Attach the declared return type as part of the function contract.
        $this->expect(\tokenize\TOKEN_COLON, "':' before return type");
        $type = $this->expression(\parse\EXPR_TYPE);
        $this->result->tree->child($function, $type);

        // Parse the executable body separately from the declaration contract.
        $body = $this->block();
        $this->result->tree->child($function, $body);
        $this->finish($function);
        return $function;
    }

    /** Preserve declaration order and reject unsupported defaults at the parameter boundary. */
    private function parameter_list(): int
    {
        // Preserve parameter declaration order in a dedicated child list.
        $open = $this->expect(\tokenize\TOKEN_LEFT_PARENTHESIS, "'(' after function name");
        $parameters = $this->node(\parse\SYNTAX_PARAMETER_LIST, (int)$open->start, 0);

        if ($this->kind() !== \tokenize\TOKEN_RIGHT_PARENTHESIS)
        {
            while (true)
            {
                $parameter = $this->parameter();
                $this->result->tree->child($parameters, $parameter);
                if ($this->kind() !== \tokenize\TOKEN_COMMA) {
                    break;
                }
                $this->advance();
            }
        }
        $this->expect(\tokenize\TOKEN_RIGHT_PARENTHESIS, "')' after parameters; defaults and other parameter forms are unsupported");
        $this->finish($parameters);
        return $parameters;
    }

    /** Attach one typed parameter name and its annotation. */
    private function parameter(): int
    {
        $start = (int)$this->peek()->start;
        $parameter = $this->node(\parse\SYNTAX_PARAMETER_DECLARATION, $start, 0);

        $reference /** int */ = 0;
        $variable /** int */ = 0;
        $type /** int */ = 0;
        if (!(($this->kind() === \tokenize\TOKEN_IDENTIFIER) || ($this->kind() === \tokenize\TOKEN_CONST_KEYWORD))) {
            $variable = $this->variable();
            $type = $this->expression(\parse\EXPR_TYPE);
        }
        else
        {
            // Explicit references follow Simple C++ spelling; logical child order stays stable.
            $constant = $this->kind() === \tokenize\TOKEN_CONST_KEYWORD;
            if ($constant) {
                $this->advance();
            }
            $type = $this->expression(\parse\EXPR_TYPE);
            $mark = $this->expect(\tokenize\TOKEN_AMPERSAND, "'&' in a reference parameter");
            $reference = $this->node($constant ? \parse\SYNTAX_CONST_REFERENCE_ANNOTATION : \parse\SYNTAX_REFERENCE_ANNOTATION, (int)$mark->start, 0);
            $this->finish($reference);
            $variable = $this->variable();
        }
        $this->result->tree->child($parameter, $variable);
        $this->result->tree->child($parameter, $type);
        if ($reference !== 0) {
            $this->result->tree->child($parameter, $reference);
        }
        $this->finish($parameter);
        return $parameter;
    }

    /** Parse public fields and method wrappers; receiver permissions stay separate from ordinary function syntax. */
    private function struct_definition(): int
    {
        $record = $this->node(\parse\SYNTAX_STRUCT_DECLARATION, (int)$this->advance()->start, 0);

        $this->result->tree->child($record, $this->name());
        $this->expect(\tokenize\TOKEN_LEFT_BRACE, "'{' after struct name");
        while ($this->kind() !== \tokenize\TOKEN_RIGHT_BRACE)
        {
            $start = (int)$this->expect(\tokenize\TOKEN_PUBLIC_KEYWORD, 'public scalar field declaration')->start;
            if ((($this->kind() === \tokenize\TOKEN_FUNCTION_KEYWORD) || ($this->kind() === \tokenize\TOKEN_CONST_KEYWORD)))
            {
                $member = $this->node(\parse\SYNTAX_METHOD_DECLARATION, $start, 0);

                $constant = $this->kind() === \tokenize\TOKEN_CONST_KEYWORD;
                $mark = $this->peek();
                if ($constant) {
                    $mark = $this->advance();
                }
                $this->result->tree->child($member, $this->function_definition());
                if ($constant) {
                    $this->result->tree->child($member, $this->node(\parse\SYNTAX_CONST_REFERENCE_ANNOTATION, (int)$mark->start, (int)$mark->length));
                }
                $this->finish($member);
                $this->result->tree->child($record, $member);
                continue;
            }
            $field = $this->node(\parse\SYNTAX_FIELD_DECLARATION, $start, 0);

            $this->result->tree->child($field, $this->expression(\parse\EXPR_TYPE));
            $this->result->tree->child($field, $this->variable());
            if ($this->kind() === \tokenize\TOKEN_LEFT_BRACKET) {
                $this->advance();
                $this->result->tree->child($field, $this->expression(\parse\EXPR_VALUE));
                $this->expect(\tokenize\TOKEN_RIGHT_BRACKET, "']' after array extent");
            }
            $this->expect(\tokenize\TOKEN_SEMICOLON, "';'; field initializers are unsupported");
            $this->finish($field);
            $this->result->tree->child($record, $field);
        }
        $this->advance();
        $this->finish($record);
        return $record;
    }
}
