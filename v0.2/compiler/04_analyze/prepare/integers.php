<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Check decimal literals before host conversion, including optional signs admitted by global syntax. */
final class Integer_Preparation
{
	/** Return: exact signed int64; reject overflow rather than relying on host casts or negation. */
	public static function value(ast_integer_expression $syntax): int /** int64 */
	{
		$tokens = $syntax->root()->tokens;
		$negative = $tokens->tokens[$syntax->start]->kind === token_kind::minus;
		$decimal = ltrim($tokens->text_at($syntax->end - 1), '0');
		$limit = $negative ? '9223372036854775808' : '9223372036854775807';
		if ((strlen($decimal) > strlen($limit))
			|| ((strlen($decimal) === strlen($limit)) && (strcmp($decimal, $limit) > 0))) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Integer literal is outside int64 range');
		}
		if ($negative && ($decimal === $limit)) {
			return -\PHP_INT_MAX - 1;
		}
		$value = $decimal === '' ? 0 : (int) $decimal;
		return $negative ? -$value : $value;
	}
}
