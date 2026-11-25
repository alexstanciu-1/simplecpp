<?php
declare(strict_types=1);

/*
 * Role: Binary syntax vocabulary, precedence and lexical template-suffix disambiguation.
 * Used by: File_Parser; binding and body expression consumers
 * Call map: from_token(); operation(); precedence(); angle_ends()
 * No declaration lookup or type-dependent parsing; scratch indexing is linear in tokens.
 */
namespace parse;
// <scpp-imports>
use function scpp\string_byte_from_int as string_byte_from_int;
use function scpp\enum_name as enum_name;
use function scpp\lock_empty as lock_empty;
use function scpp\lock_try as lock_try;
use function scpp\lock_release as lock_release;
use function scpp\lock_transfer as lock_transfer;
use function scpp\process_spawn as process_spawn;
use function scpp\process_poll as process_poll;
use function scpp\process_output as process_output;
use function scpp\process_stop as process_stop;
use function scpp\process_close as process_close;
use function scpp\sequence_map as sequence_map;
use function scpp\sequence_filter as sequence_filter;
use function scpp\keyed_map as keyed_map;
use function scpp\keyed_filter as keyed_filter;
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>


final class Binary_Syntax
{
    /** Map supported value operators independently from their semantic permission. */
    public static function from_token(\tokenize\token_kind $kind): ?syntax_kind
    {
        if ($kind === \tokenize\token_kind::plus) { return syntax_kind::addition_expression; }
        if ($kind === \tokenize\token_kind::left_angle) { return syntax_kind::less_than_expression; }
        return null;
    }

    /** Share semantic operator names across binding and body checking. */
    public static function operation(syntax_kind $kind): ?string
    {
        if ($kind === syntax_kind::addition_expression) { return 'addition'; }
        if ($kind === syntax_kind::less_than_expression) { return 'less_than'; }
        return null;
    }

    /** Keep postfix syntax tighter than arithmetic, and arithmetic tighter than comparison. */
    public static function precedence(syntax_kind $kind): int
    {
        if (($kind !== syntax_kind::addition_expression) && ($kind !== syntax_kind::less_than_expression)) {
            throw new \LogicException('Not a binary operator');
        }
        return $kind === syntax_kind::addition_expression ? 2 : 1;
    }

    /** Pair angles within their parenthesis/bracket scope once, avoiding repeated forward scans. */
    public static function angle_ends(\tokenize\Token_Buffer $tokens): array /** hash<int,int> */
    {
        $ends /** hash<int,int> */ = [];
        $scopes /** vector<Angle_Scope> */ = [];
        $scopes[] = new Angle_Scope();
        $depth = 0;
        foreach ($tokens->rows as $index => $token)
        {
            $kind = $token->kind;
            if (($kind === \tokenize\token_kind::left_parenthesis) || ($kind === \tokenize\token_kind::left_bracket)) {
                $depth++;
                if ($depth === count($scopes)) { $scopes[] = new Angle_Scope(); }
                else { $scopes[$depth]->clear(); }
            }
            elseif (($kind === \tokenize\token_kind::right_parenthesis) || ($kind === \tokenize\token_kind::right_bracket)) {
                if ($depth > 0) { $depth = $depth - 1; }
            }
            elseif ($kind === \tokenize\token_kind::left_angle) {
                $scopes[$depth]->push($index);
            }
            elseif ($kind === \tokenize\token_kind::right_angle) {
                if (!$scopes[$depth]->is_empty()) {
                    $opening = $scopes[$depth]->pop();
                    $ends[$opening] = $index;
                }
            }
            elseif (($kind === \tokenize\token_kind::semicolon) || ($kind === \tokenize\token_kind::left_brace) || ($kind === \tokenize\token_kind::right_brace)) {
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
        if ($this->used === count($this->entries)) { $this->entries[] = $index; }
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
