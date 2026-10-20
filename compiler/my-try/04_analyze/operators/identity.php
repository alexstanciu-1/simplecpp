<?php

/* Strict scalar identity preserves source kinds; it never selects numeric promotion. */
namespace scpp\compiler;

final class Scalar_Identity
{
	/** Admitted distinct scalar kinds: canonical bool, int, float and string; no coercion. */
	public static function is_distinct_kind_pair(canonical_type_use $left,
		canonical_type_use $right, preparation_context $context): bool
	{
		return !$left->matches($right) && self::is_canonical_scalar($left, $context)
			&& self::is_canonical_scalar($right, $context);
	}

	private static function is_canonical_scalar(canonical_type_use $type, preparation_context $context): bool
	{
		return $type->matches($context->integer) || $type->matches($context->boolean)
			|| $type->matches($context->string_type)
			|| $type->matches(Language_Types::floating(Model::$language_scope));
	}

	public static function try_decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): ?operator_decision
	{
		if ((q_count($operands) !== 2)
			|| (($source_operator !== operator_kind::identical) && ($source_operator !== operator_kind::not_identical))) {
			return null;
		}
		$same_string = String_Conversions::is_string($operands[0]) && String_Conversions::is_string($operands[1]);
		$same_bool = $operands[0]->matches($context->boolean) && $operands[1]->matches($context->boolean);
		if (!$same_string && !$same_bool && !self::is_distinct_kind_pair($operands[0], $operands[1], $context)) {
			return null;
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->operation = $source_operator === operator_kind::identical
			? operator_operation::scalar_identical : operator_operation::scalar_not_identical;
		$decision->result_type = $context->boolean;
		foreach ($operands as $operand) {
			$decision->operands[] = Conversion_Preparation::decide(
				$operand, $operand, conversion_context::operator_operand);
		}
		return $decision;
	}
}
