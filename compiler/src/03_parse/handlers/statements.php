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

/**
 * @compiler-internal File_Parser statement dispatch and related small handlers.
 * Handlers start at the first token, consume all delimiters and return a finished
 * node for the caller to attach. State and token/tree helpers belong to File_Parser.
 */
trait Statement_Parsing
{
    /** Collect declarations separately while attaching executable statements in source order. */
    private function statements(int $block, int $terminator, bool $file_scope): void
    {

        while ($this->kind() !== $terminator)
        {
            if ($this->kind() === \tokenize\TOKEN_END_OF_FILE) {
                $this->fail("Expected '}' to close block");
            }
            $declaration = $this->declaration($file_scope);
            if ($declaration !== 0) {
                $this->result->definitions[] = $declaration;
            }
            else {
                $statement = $this->statement();
                $this->result->tree->child($block, $statement);
            }
        }
    }

    /** Select a statement parser from the leading token; reject unsupported syntax explicitly. */
    private function statement(): int
    {
        $kind = $this->kind();
        if ($kind === \tokenize\TOKEN_LEFT_BRACE) {
            return $this->block();
        }
        elseif (($kind === \tokenize\TOKEN_IF_KEYWORD) || ($kind === \tokenize\TOKEN_WHILE_KEYWORD)) {
            return $this->control_statement();
        }
        elseif ($kind === \tokenize\TOKEN_CONST_KEYWORD) {
            return $this->constant_definition();
        }
        elseif ($kind === \tokenize\TOKEN_ECHO_KEYWORD) {
            return $this->echo_statement();
        }
        elseif ($kind === \tokenize\TOKEN_RETURN_KEYWORD) {
            return $this->return_statement();
        }
        elseif ($kind === \tokenize\TOKEN_VARIABLE_NAME) {
            return $this->variable_statement();
        }
        elseif (($kind === \tokenize\TOKEN_NEW_KEYWORD) || ($kind === \tokenize\TOKEN_IDENTIFIER) || ($kind === \tokenize\TOKEN_INTEGER_LITERAL)
            || ($kind === \tokenize\TOKEN_STRING_LITERAL) || ($kind === \tokenize\TOKEN_BOOLEAN_LITERAL) || ($kind === \tokenize\TOKEN_LEFT_PARENTHESIS)) {
            return $this->expression_statement();
        }
        else {
            $this->fail('Expected return, block, local declaration, assignment or expression statement');
            return 0;
        }
    }

    /** Parse one braced lexical block through the common statement path. */
    private function block(): int
    {
        $start = (int)$this->expect(\tokenize\TOKEN_LEFT_BRACE, "'{' to open block")->start;
        $block = $this->node(\parse\SYNTAX_BLOCK, $start, 0);
        $this->statements($block, \tokenize\TOKEN_RIGHT_BRACE, false);
        $this->expect(\tokenize\TOKEN_RIGHT_BRACE, "'}'");
        $this->finish($block);
        return $block;
    }

    /** Preserve ordered echo operands; checking treats each as its own output/full-expression boundary. */
    private function echo_statement(): int
    {
        $statement = $this->node(\parse\SYNTAX_ECHO_STATEMENT, (int)$this->advance()->start, 0);

        while (true)
        {
            $value = $this->expression(\parse\EXPR_VALUE);
            $this->result->tree->child($statement, $value);
            if ($this->kind() !== \tokenize\TOKEN_COMMA) {
                break;
            }
            $this->advance();
        }
        return $this->finish_simple_statement($statement);
    }

    /** Parse an optional return expression and its required terminator. */
    private function return_statement(): int
    {
        $start = (int)$this->advance()->start;
        $statement = $this->node(\parse\SYNTAX_RETURN_STATEMENT, $start, 0);

        if ($this->kind() !== \tokenize\TOKEN_SEMICOLON) {
            $value = $this->expression(\parse\EXPR_VALUE);
            $this->result->tree->child($statement, $value);
        }
        return $this->finish_simple_statement($statement);
    }

    /** Distinguish typed initialization, assignment and ordinary variable expressions. */
    private function variable_statement(): int
    {
        $start = (int)$this->peek()->start;
        $following = (int)$this->result->tokens->rows[$this->cursor + 1]->kind;

        $statement /** int */ = 0;
        if ($following === \tokenize\TOKEN_IDENTIFIER)
        {
            // A typed declaration can omit construction syntax; type checking decides eligibility.
            $variable = $this->variable();
            $statement = $this->node(\parse\SYNTAX_LOCAL_DECLARATION, $start, 0);
            $this->result->tree->child($statement, $variable);
            $type = $this->expression(\parse\EXPR_TYPE);
            $this->result->tree->child($statement, $type);
            if ($this->kind() !== \tokenize\TOKEN_SEMICOLON) {
                $this->expect(\tokenize\TOKEN_ASSIGNMENT, "'=' or ';' after local type");
                $initializer = $this->expression(\parse\EXPR_VALUE);
                $this->result->tree->child($statement, $initializer);
            }
        }
        else
        {
            // Parse every location/read through the same expression grammar before checking assignment.
            $value = $this->expression(\parse\EXPR_VALUE);
            if ($this->kind() === \tokenize\TOKEN_ASSIGNMENT) {
                $this->advance();
                $statement = $this->node(\parse\SYNTAX_ASSIGNMENT_STATEMENT, $start, 0);
                $this->result->tree->child($statement, $value);
                $value = $this->expression(\parse\EXPR_VALUE);
            }
            else {
                $statement = $this->node(\parse\SYNTAX_EXPRESSION_STATEMENT, $start, 0);
            }
            $this->result->tree->child($statement, $value);
        }
        return $this->finish_simple_statement($statement);
    }

    /** Preserve an ordinary discarded expression through the shared expression parser. */
    private function expression_statement(): int
    {
        $statement = $this->node(\parse\SYNTAX_EXPRESSION_STATEMENT, (int)$this->peek()->start, 0);

        $value = $this->expression(\parse\EXPR_VALUE);
        $this->result->tree->child($statement, $value);
        return $this->finish_simple_statement($statement);
    }

    private function finish_simple_statement(int $statement): int
    {
        $this->expect(\tokenize\TOKEN_SEMICOLON, "';' after statement");
        $this->finish($statement);
        return $statement;
    }
}
