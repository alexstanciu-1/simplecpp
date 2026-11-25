<?php
declare(strict_types=1);

/*
 * Role: Binary syntax vocabulary, precedence and lexical template-suffix disambiguation.
 * Used by: File_Parser; binding and body expression consumers
 * Call map: from_token(); operation(); precedence(); angle_ends()
 * No declaration lookup or type-dependent parsing; scratch indexing is linear in tokens.
 */
namespace parse;

use tokenize\token_kind;

// Frozen adoption oracle; only the class name differs.
final class Baseline_Binary_Syntax
{
    /** Map supported value operators independently from their semantic permission. */
    public static function from_token(token_kind $kind): ?syntax_kind
    {
        return match ($kind) {
            token_kind::plus => syntax_kind::addition_expression,
            token_kind::left_angle => syntax_kind::less_than_expression,
            default => null,
        };
    }

    /** Share semantic operator names across binding and body checking. */
    public static function operation(syntax_kind $kind): ?string
    {
        return match ($kind) {
            syntax_kind::addition_expression => 'addition',
            syntax_kind::less_than_expression => 'less_than',
            default => null,
        };
    }

    /** Keep postfix syntax tighter than arithmetic, and arithmetic tighter than comparison. */
    public static function precedence(syntax_kind $kind): int
    {
        return match ($kind) {
            syntax_kind::addition_expression => 2,
            syntax_kind::less_than_expression => 1,
            default => throw new \LogicException('Not a binary operator'),
        };
    }

    /** Pair angles within their parenthesis/bracket scope once, avoiding repeated forward scans. */
    public static function angle_ends(\tokenize\Token_Buffer $tokens): array
    {
        $ends = [];
        $scopes = [[]];
        foreach ($tokens->rows as $index => $token)
        {
            $scope = count($scopes) - 1;
            if (in_array($token->kind, [token_kind::left_parenthesis, token_kind::left_bracket], true)) {
                $scopes[] = [];
            }
            elseif (in_array($token->kind, [token_kind::right_parenthesis, token_kind::right_bracket], true)) {
                if ($scope > 0) {
                    array_pop($scopes);
                }
            }
            elseif ($token->kind === token_kind::left_angle) {
                $scopes[$scope][] = $index;
            }
            elseif (($token->kind === token_kind::right_angle) && ($scopes[$scope] !== [])) {
                $ends[array_pop($scopes[$scope])] = $index;
            }
            elseif (in_array($token->kind, [token_kind::semicolon, token_kind::left_brace, token_kind::right_brace], true)) {
                $scopes = [[]];
            }
        }
        return $ends;
    }
}
