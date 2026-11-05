<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Byte-output strategy: preserve literal bytes and evaluate string objects exactly once. */
final class Cpp_Output
{
	/** Emit echo "text" without storage, or retain a returned string until both byte-span queries complete. */
	public static function bytes(ast_expression $value, ?string $operand, string $symbol): string
	{
		if ($value instanceof ast_string_expression) {
			return $symbol . '(' . Cpp_String_Literals::bytes($value->bytes()) . ', ' . strlen($value->bytes()) . ');';
		}
		if ($operand === null) {
			throw new \LogicException('Missing output operand spelling');
		}
		return '{ const auto& output_value = ' . $operand . '; ' . $symbol
			. '(output_value.data(), output_value.size()); }';
	}
}
