<?php
declare(strict_types=1);

/*
 * Role: Binary syntax vocabulary, precedence and lexical template-suffix disambiguation.
 * Used by: File_Parser; binding and body expression consumers
 * Call map: from_token(); operation(); precedence(); angle_ends()
 * No declaration lookup or type-dependent parsing; scratch indexing is linear in tokens.
 */
namespace parse;


final class Binary_Syntax
{
    /** Map supported value operators independently from their semantic permission. */
    public static function from_token(int $kind): int
    {
        if ($kind === \tokenize\TOKEN_PLUS) { return \parse\SYNTAX_ADDITION_EXPRESSION; }
        if ($kind === \tokenize\TOKEN_LEFT_ANGLE) { return \parse\SYNTAX_LESS_THAN_EXPRESSION; }
        return 0;
    }

    /** Share semantic operator names across binding and body checking. */
    public static function operation(int $kind): string
    {
        if ($kind === \parse\SYNTAX_ADDITION_EXPRESSION) { return 'addition'; }
        if ($kind === \parse\SYNTAX_LESS_THAN_EXPRESSION) { return 'less_than'; }
        return '';
    }

    /** Keep postfix syntax tighter than arithmetic, and arithmetic tighter than comparison. */
    public static function precedence(int $kind): int
    {
        if (($kind !== \parse\SYNTAX_ADDITION_EXPRESSION) && ($kind !== \parse\SYNTAX_LESS_THAN_EXPRESSION)) {
            throw new \LogicException('Not a binary operator');
        }
        return $kind === \parse\SYNTAX_ADDITION_EXPRESSION ? 2 : 1;
    }

    /** Pair angles within their parenthesis/bracket scope once, avoiding repeated forward scans. */
    public static function angle_ends(\tokenize\Lexical_Buffer $tokens): array /** hash<int,int> */
    {
        $ends /** hash<int,int> */ = [];
        $scopes /** vector<Angle_Scope> */ = [];
        $scopes[] = new Angle_Scope();
        $depth = 0;
        foreach ($tokens->rows as $index => $token)
        {
            $kind /** int */ = (int)$token->kind;
            if (($kind === \tokenize\TOKEN_LEFT_PARENTHESIS) || ($kind === \tokenize\TOKEN_LEFT_BRACKET)) {
                $depth++;
                if ($depth === q_count($scopes)) { $scopes[] = new Angle_Scope(); }
                else { $scopes[$depth]->clear(); }
            }
            elseif (($kind === \tokenize\TOKEN_RIGHT_PARENTHESIS) || ($kind === \tokenize\TOKEN_RIGHT_BRACKET)) {
                if ($depth > 0) { $depth = $depth - 1; }
            }
            elseif ($kind === \tokenize\TOKEN_LEFT_ANGLE) {
                $scopes[$depth]->push($index);
            }
            elseif ($kind === \tokenize\TOKEN_RIGHT_ANGLE) {
                if (!$scopes[$depth]->is_empty()) {
                    $opening = $scopes[$depth]->pop();
                    $ends[$opening] = $index;
                }
            }
            elseif (($kind === \tokenize\TOKEN_SEMICOLON) || ($kind === \tokenize\TOKEN_LEFT_BRACE) || ($kind === \tokenize\TOKEN_RIGHT_BRACE)) {
                $depth = 0;
                $scopes[0]->clear();
            }
        }
        return $ends;
    }
}

/** @compiler-internal Reusable lexical scratch stack; logical size is separate from capacity. */
class Angle_Scope
{
    private array $entries /** vector<int> */ = [];
    private int $used = 0;

    public function push(int $index): void
    {
        if ($this->used === q_count($this->entries)) { $this->entries[] = $index; }
        else { $this->entries[$this->used] = $index; }
        $this->used++;
    }

    public function pop(): int
    {
        if ($this->used === 0) { throw new \LogicException('Empty angle scope'); }
        $this->used = $this->used - 1;
        return $this->entries[$this->used];
    }

    public function is_empty(): bool { return $this->used === 0; }
    public function clear(): void { $this->used = 0; }
}
