<?php
declare(strict_types=1);

/*
 * Role: Preserve runtime/compile-time conditionals and braced loops.
 * Used by: File_Parser (private trait methods on this owner)
 * Call map:
 *   control_statement()
 *     -> expression(); block()
 */

namespace parse;

/**
 * @compiler-internal File_Parser handlers for conditional/loop statements.
 * Called at an if/while keyword; consumes any condition and the bodies, returning a
 * finished node. Uses the owner's expression/tree helpers and statement blocks.
 */
trait Control_Statement_Parsing
{
    /** Preserve runtime/constexpr conditions and consteval context selection, including ordered else-if branches. */
    private function control_statement(): int
    {
        $token = $this->advance();
        $kind = (int)$token->kind === \tokenize\TOKEN_IF_KEYWORD ? \parse\SYNTAX_IF_STATEMENT : \parse\SYNTAX_WHILE_STATEMENT;
        if (($kind === \parse\SYNTAX_IF_STATEMENT)
            && (($this->kind() === \tokenize\TOKEN_CONSTEXPR_KEYWORD) || ($this->kind() === \tokenize\TOKEN_CONSTEVAL_KEYWORD))) {
            $kind = (int)$this->advance()->kind === \tokenize\TOKEN_CONSTEXPR_KEYWORD
                ? \parse\SYNTAX_CONSTEXPR_IF_STATEMENT : \parse\SYNTAX_CONSTEVAL_IF_STATEMENT;
        }
        $id = $this->node($kind, (int)$token->start, 0);

        if ($kind !== \parse\SYNTAX_CONSTEVAL_IF_STATEMENT) {
            $this->expect(\tokenize\TOKEN_LEFT_PARENTHESIS, "'(' before condition");
            $this->result->tree->child($id, $this->expression(\parse\EXPR_VALUE));
            $this->expect(\tokenize\TOKEN_RIGHT_PARENTHESIS, "')' after condition");
        }
        $body = $this->block();
        $this->result->tree->child($id, $body);
        if (($kind !== \parse\SYNTAX_WHILE_STATEMENT) && ($this->kind() === \tokenize\TOKEN_ELSE_KEYWORD)) {
            $this->advance();
            $alternative = (($kind !== \parse\SYNTAX_CONSTEVAL_IF_STATEMENT) && ($this->kind() === \tokenize\TOKEN_IF_KEYWORD))
                ? $this->control_statement() : $this->block();
            $this->result->tree->child($id, $alternative);
        }
        $this->finish($id);
        return $id;
    }
}
