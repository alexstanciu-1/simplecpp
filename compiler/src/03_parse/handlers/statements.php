<?php
declare(strict_types=1);

/*
 * Role: Parse ordered statements and blocks.
 * Used by: File_Parser (private trait methods on this owner)
 * Call map:
 *   statements()
 *     -> declaration() [each item]; statement() [if no declaration]
 */

namespace parse;

use tokenize\token_kind;

/**
 * @compiler-internal File_Parser statement dispatch and related small handlers.
 * Handlers start at the first token, consume all delimiters and return a finished
 * node for the caller to attach. State and token/tree helpers belong to File_Parser.
 */
trait Statement_Parsing
{
    /** Collect declarations separately while attaching executable statements in source order. */
    private function statements(int $block, token_kind $terminator, bool $file_scope): void
    {
        $last = 0;
        while ($this->peek()->kind !== $terminator)
        {
            if ($this->peek()->kind === token_kind::end_of_file) {
                $this->fail("Expected '}' to close block");
            }
            $declaration = $this->declaration($file_scope);
            if ($declaration !== null) {
                $this->definitions[] = $declaration;
            }
            else {
                $statement = $this->statement();
                $this->child($block, $last, $statement);
            }
        }
    }

    /** Select a statement parser from the leading token; reject unsupported syntax explicitly. */
    private function statement(): int
    {
        $kind = $this->peek()->kind;
        if ($kind === token_kind::left_brace) {
            return $this->block();
        }
        elseif (($kind === token_kind::if_keyword) || ($kind === token_kind::while_keyword)) {
            return $this->control_statement();
        }
        elseif ($kind === token_kind::const_keyword) {
            return $this->constant_definition();
        }
        elseif ($kind === token_kind::echo_keyword) {
            return $this->echo_statement();
        }
        elseif ($kind === token_kind::return_keyword) {
            return $this->return_statement();
        }
        elseif ($kind === token_kind::variable_name) {
            return $this->variable_statement();
        }
        elseif (($kind === token_kind::new_keyword) || ($kind === token_kind::identifier) || ($kind === token_kind::integer_literal)
            || ($kind === token_kind::string_literal) || ($kind === token_kind::boolean_literal) || ($kind === token_kind::left_parenthesis)) {
            return $this->expression_statement();
        }
        else {
            $this->fail('Expected return, block, local declaration, assignment or expression statement');
        }
    }

    /** Parse one braced lexical block through the common statement path. */
    private function block(): int
    {
        $start = $this->expect(token_kind::left_brace, "'{' to open block")->start;
        $block = $this->node(syntax_kind::block, $start);
        $this->statements($block, token_kind::right_brace, false);
        $this->expect(token_kind::right_brace, "'}'");
        $this->finish($block);
        return $block;
    }

    /** Preserve ordered echo operands; checking treats each as its own output/full-expression boundary. */
    private function echo_statement(): int
    {
        $statement = $this->node(syntax_kind::echo_statement, $this->advance()->start);
        $last = 0;
        do
        {
            $value = $this->expression();
            $this->child($statement, $last, $value);
            if ($this->peek()->kind !== token_kind::comma) {
                break;
            }
            $this->advance();
        }
        while (true);
        return $this->finish_simple_statement($statement);
    }

    /** Parse an optional return expression and its required terminator. */
    private function return_statement(): int
    {
        $start = $this->advance()->start;
        $statement = $this->node(syntax_kind::return_statement, $start);
        $last = 0;
        if ($this->peek()->kind !== token_kind::semicolon) {
            $value = $this->expression();
            $this->child($statement, $last, $value);
        }
        return $this->finish_simple_statement($statement);
    }

    /** Distinguish typed initialization, assignment and ordinary variable expressions. */
    private function variable_statement(): int
    {
        $start = $this->peek()->start;
        $following = $this->tokens->rows[$this->cursor + 1]->kind;
        $last = 0;
        if ($following === token_kind::identifier)
        {
            // A typed declaration can omit construction syntax; type checking decides eligibility.
            $variable = $this->variable();
            $statement = $this->node(syntax_kind::local_declaration, $start);
            $this->child($statement, $last, $variable);
            $type = $this->type_syntax('local type name');
            $this->child($statement, $last, $type);
            if ($this->peek()->kind !== token_kind::semicolon) {
                $this->expect(token_kind::assignment, "'=' or ';' after local type");
                $initializer = $this->expression();
                $this->child($statement, $last, $initializer);
            }
        }
        else
        {
            // Parse every location/read through the same expression grammar before checking assignment.
            $value = $this->expression();
            if ($this->peek()->kind === token_kind::assignment) {
                $this->advance();
                $statement = $this->node(syntax_kind::assignment_statement, $start);
                $this->child($statement, $last, $value);
                $value = $this->expression();
            }
            else {
                $statement = $this->node(syntax_kind::expression_statement, $start);
            }
            $this->child($statement, $last, $value);
        }
        return $this->finish_simple_statement($statement);
    }

    /** Preserve an ordinary discarded expression through the shared expression parser. */
    private function expression_statement(): int
    {
        $statement = $this->node(syntax_kind::expression_statement, $this->peek()->start);
        $last = 0;
        $value = $this->expression();
        $this->child($statement, $last, $value);
        return $this->finish_simple_statement($statement);
    }

    private function finish_simple_statement(int $statement): int
    {
        $this->expect(token_kind::semicolon, "';' after statement");
        $this->finish($statement);
        return $statement;
    }
}
