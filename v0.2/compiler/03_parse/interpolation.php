<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Parse only flavor-specific insertion boundaries; escape decoding and binding belong to preparation. */
final class Interpolation
{
	/** Split a whole quoted token only when it contains a named insertion. */
	public static function parse(token_list $tokens, int $token /** uint32 */): ast_expression
	{
		$text = $tokens->text_at($token);
		$flavor = $tokens->source->flavor;
		if ($flavor === flavor::php && !str_contains($text, '$')) {
			return new ast_string_expression($token, $token + 1);
		}
		$start = $flavor === flavor::cpp ? 2 : 1;
		$end = strlen($text) - 1;
		$parts = [];
		$pattern = $flavor === flavor::js ? '/\G[A-Za-z_$][A-Za-z_$0-9]*/' : '/\G[A-Za-z_][A-Za-z_0-9]*/';
		for ($i = $start; $i < $end; ++$i) {
			$c = $text[$i];
			$next = $text[$i + 1];
			if ($c === '\\') {
				++$i;
				continue;
			}
			$braced = true;
			if ($flavor === flavor::cpp) {
				if ($c !== '{' && $c !== '}') {
					continue;
				}
				if ($next === $c) {
					++$i;
					continue;
				}
				if ($c === '}') {
					throw new diagnostic($tokens->source, $tokens->tokens[$token]->offset + $i, 'Unmatched interpolation brace');
				}
				$name_at = $i + 1;
			}
			elseif ($flavor === flavor::js) {
				if ($c !== '$' || $next !== '{') {
					continue;
				}
				$name_at = $i + 2;
			}
			else {
				$braced = $c === '{' && $next === '$';
				if (!$braced && $c !== '$') {
					continue;
				}
				$name_at = $i + ($braced ? 2 : 1);
			}
			if (!preg_match($pattern, $text, $match, 0, $name_at)) {
				if ($flavor === flavor::php && !$braced && !str_contains('{$', $next) && ord($next) < 128) {
					continue;
				}
				throw new diagnostic($tokens->source, $tokens->tokens[$token]->offset + $i, 'Expected an interpolation name');
			}
			$after = $name_at + strlen($match[0]);
			if ($braced && $text[$after] !== '}') {
				throw new diagnostic($tokens->source, $tokens->tokens[$token]->offset + $after, 'Expected closing interpolation brace');
			}
			if (!$braced && (str_contains('[(', $text[$after]) || ord($text[$after]) >= 128 || substr($text, $after, 2) === '->')) {
				throw new diagnostic($tokens->source, $tokens->tokens[$token]->offset + $after, 'Unsupported interpolation expression or suffix');
			}
			if ($start < $i) {
				$parts[] = new ast_interpolation_text($token, $start, $i - $start);
			}
			$parts[] = new ast_interpolation_name($match[0], $token, $name_at);
			$start = $after + ($braced ? 1 : 0);
			$i = $start - 1;
		}
		if ($parts === []) {
			return new ast_string_expression($token, $token + 1);
		}
		if ($start < $end) {
			$parts[] = new ast_interpolation_text($token, $start, $end - $start);
		}
		return new ast_interpolated_string($token, $parts);
	}
}
