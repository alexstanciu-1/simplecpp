<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Output bindings for literal bytes and borrowed string values. */
final class Cpp_Output
{
	/** Literals supply their decoded byte span. String expressions bind directly to the runtime parameter. */
	public static function string(ast_expression $value, ?string $operand, string $symbol, string $literal_symbol): string
	{
		if ($value instanceof ast_string_expression) {
			return $literal_symbol . '(' . Cpp_String_Literals::bytes($value->bytes()) . ', ' . strlen($value->bytes()) . ');';
		}
		if ($operand === null) {
			throw new \LogicException('Missing output operand spelling');
		}
		return $symbol . '(' . $operand . ');';
	}
}
