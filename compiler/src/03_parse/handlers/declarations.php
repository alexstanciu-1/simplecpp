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

use tokenize\token_kind;

/**
 * @compiler-internal File_Parser declaration recognition and complete handlers.
 * A null declaration consumes no tokens and allocates no nodes. Recognized
 * declarations validate their context before parsing; the statement list owns
 * registration in definitions. State and token/tree helpers belong to File_Parser.
 */
trait Declaration_Parsing
{
    /** Recognize a file-level declaration without consuming ordinary statement tokens. */
    private function declaration(bool $file_scope): ?int
    {
        $kind = $this->peek()->kind;
        if (($kind === token_kind::const_keyword) && ($file_scope)) {
            return $this->constant_definition();
        }
        if (!in_array($kind, [token_kind::function_keyword, token_kind::struct_keyword, token_kind::template_keyword,
            token_kind::constexpr_keyword, token_kind::consteval_keyword], true)) {
            return null;
        }
        if (!$file_scope) {
            $this->fail('Nested function definitions and struct/template declarations are unsupported');
        }
        return match ($kind) {
            token_kind::function_keyword => $this->function_definition(),
            token_kind::struct_keyword => $this->struct_definition(),
            token_kind::template_keyword => $this->template_definition(),
            default => $this->evaluated_function(),
        };
    }

    /** Attach the function name, parameter/result contract and separately parsed body. */
    private function function_definition(): int
    {
        // Create the declaration and attach its name before its contract and body.
        $start = $this->expect(token_kind::function_keyword, "'function'")->start;
        $function = $this->node(syntax_kind::function_declaration, $start);
        $last = 0;
        $name = $this->name('function name');
        $this->child($function, $last, $name);

        $parameters = $this->parameter_list();
        $this->child($function, $last, $parameters);

        // Attach the declared return type as part of the function contract.
        $this->expect(token_kind::colon, "':' before return type");
        $type = $this->type_syntax('return-type name');
        $this->child($function, $last, $type);

        // Parse the executable body separately from the declaration contract.
        $body = $this->block();
        $this->child($function, $last, $body);
        $this->finish($function);
        return $function;
    }

    /** Preserve declaration order and reject unsupported defaults at the parameter boundary. */
    private function parameter_list(): int
    {
        // Preserve parameter declaration order in a dedicated child list.
        $open = $this->expect(token_kind::left_parenthesis, "'(' after function name");
        $parameters = $this->node(syntax_kind::parameter_list, $open->start);
        $last_parameter = 0;
        if ($this->peek()->kind !== token_kind::right_parenthesis)
        {
            do
            {
                $parameter = $this->parameter();
                $this->child($parameters, $last_parameter, $parameter);
                if ($this->peek()->kind !== token_kind::comma) {
                    break;
                }
                $this->advance();
            }
            while (true);
        }
        $this->expect(token_kind::right_parenthesis, "')' after parameters; defaults and other parameter forms are unsupported");
        $this->finish($parameters);
        return $parameters;
    }

    /** Attach one typed parameter name and its annotation. */
    private function parameter(): int
    {
        $start = $this->peek()->start;
        $parameter = $this->node(syntax_kind::parameter_declaration, $start);
        $last = 0;
        $reference = null;
        if (!in_array($this->peek()->kind, [token_kind::identifier, token_kind::const_keyword], true)) {
            $variable = $this->variable();
            $type = $this->type_syntax('parameter type name');
        }
        else
        {
            // Explicit references follow Simple C++ spelling; logical child order stays stable.
            $constant = $this->peek()->kind === token_kind::const_keyword;
            if ($constant) {
                $this->advance();
            }
            $type = $this->type_syntax('parameter type name');
            $mark = $this->expect(token_kind::ampersand, "'&' in a reference parameter");
            $reference = $this->node($constant ? syntax_kind::const_reference_annotation : syntax_kind::reference_annotation, $mark->start);
            $this->finish($reference);
            $variable = $this->variable();
        }
        $this->child($parameter, $last, $variable);
        $this->child($parameter, $last, $type);
        if ($reference !== null) {
            $this->child($parameter, $last, $reference);
        }
        $this->finish($parameter);
        return $parameter;
    }

    /** Parse public fields and method wrappers; receiver permissions stay separate from ordinary function syntax. */
    private function struct_definition(): int
    {
        $record = $this->node(syntax_kind::struct_declaration, $this->advance()->start);
        $last = 0;
        $this->child($record, $last, $this->name('struct name'));
        $this->expect(token_kind::left_brace, "'{' after struct name");
        while ($this->peek()->kind !== token_kind::right_brace)
        {
            $start = $this->expect(token_kind::public_keyword, 'public scalar field declaration')->start;
            if (in_array($this->peek()->kind, [token_kind::function_keyword, token_kind::const_keyword], true))
            {
                $member = $this->node(syntax_kind::method_declaration, $start);
                $tail = 0;
                $constant = $this->peek()->kind === token_kind::const_keyword;
                if ($constant) {
                    $mark = $this->advance();
                }
                $this->child($member, $tail, $this->function_definition());
                if ($constant) {
                    $this->child($member, $tail, $this->node(syntax_kind::const_reference_annotation, $mark->start, $mark->length));
                }
                $this->finish($member);
                $this->child($record, $last, $member);
                continue;
            }
            $field = $this->node(syntax_kind::field_declaration, $start);
            $tail = 0;
            $this->child($field, $tail, $this->type_syntax());
            $this->child($field, $tail, $this->variable());
            if ($this->peek()->kind === token_kind::left_bracket) {
                $this->advance();
                $this->child($field, $tail, $this->expression());
                $this->expect(token_kind::right_bracket, "']' after array extent");
            }
            $this->expect(token_kind::semicolon, "';'; field initializers are unsupported");
            $this->finish($field);
            $this->child($record, $last, $field);
        }
        $this->advance();
        $this->finish($record);
        return $record;
    }
}
