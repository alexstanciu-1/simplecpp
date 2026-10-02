<?php

/* Exact bounded integer constants, independent of backend spelling and host width. */
namespace scpp\compiler;

final class Integer_Constants
{
	public static function reference(prepared_constant_reference $facts): string
	{
		if (!($facts->definition instanceof integer_constant_definition)) {
			throw new \RuntimeException('S2S requires a resolved integer constant');
		}
		$definition = object_cast($facts->definition, integer_constant_definition::class);
		return $definition->decimal;
	}

	/** Unary signs preserve exact decimal identity; arithmetic folding remains deferred. */
	public static function unary(unary_expression_node $syntax): string
	{
		$operation = $syntax->require_unary_preparation()->decision->operation;
		if (($operation !== operator_operation::integer_positive) && ($operation !== operator_operation::integer_negative)) {
			throw new \RuntimeException('S2S requires a supported constant integer expression');
		}
		$value = $syntax->operand->integer_constant();
		if (($operation === operator_operation::integer_positive) || ($value === '0')) { return $value; }
		if ($value === '-9223372036854775808') {
			throw new \RuntimeException('S2S integer constant negation exceeds signed 64-bit representation');
		}
		if (string_byte_starts_with($value, '-')) { return string_byte_slice($value, 1, string_byte_len($value) - 1); }
		return '-' . $value;
	}
}
