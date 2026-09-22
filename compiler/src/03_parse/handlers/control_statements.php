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

use tokenize\token_kind;

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
        $kind = $token->kind === token_kind::if_keyword ? syntax_kind::if_statement : syntax_kind::while_statement;
        if (($kind === syntax_kind::if_statement)
            && in_array($this->peek()->kind, [token_kind::constexpr_keyword, token_kind::consteval_keyword], true)) {
            $kind = $this->advance()->kind === token_kind::constexpr_keyword
                ? syntax_kind::constexpr_if_statement : syntax_kind::consteval_if_statement;
        }
        $id = $this->node($kind, $token->start);
        $last = 0;
        if ($kind !== syntax_kind::consteval_if_statement) {
            $this->expect(token_kind::left_parenthesis, "'(' before condition");
            $this->child($id, $last, $this->expression());
            $this->expect(token_kind::right_parenthesis, "')' after condition");
        }
        $body = $this->block();
        $this->child($id, $last, $body);
        if (($kind !== syntax_kind::while_statement) && ($this->peek()->kind === token_kind::else_keyword)) {
            $this->advance();
            $alternative = (($kind !== syntax_kind::consteval_if_statement) && ($this->peek()->kind === token_kind::if_keyword))
                ? $this->control_statement() : $this->block();
            $this->child($id, $last, $alternative);
        }
        $this->finish($id);
        return $id;
    }
}
