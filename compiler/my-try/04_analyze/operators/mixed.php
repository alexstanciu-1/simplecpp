<?php

/* Mixed operators select runtime dispatch or strict identity, never scalar extraction. */
namespace scpp\compiler;

final class Mixed_Operators
{
	/** Numeric payload kinds are checked at runtime; known nonnumeric peers reject here. */
	public static function decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */): operator_decision
	{
		if (self::is_comparison($source_operator)) {
			return self::decide_comparison($source_operator, $operands);
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$unary = false;
		if ($source_operator === operator_kind::unary_plus) {
			$decision->operation = operator_operation::mixed_positive;
			$unary = true;
		}
		elseif ($source_operator === operator_kind::unary_minus) {
			$decision->operation = operator_operation::mixed_negative;
			$unary = true;
		}
		elseif ($source_operator === operator_kind::addition) {
			$decision->operation = operator_operation::mixed_addition;
		}
		elseif ($source_operator === operator_kind::subtraction) {
			$decision->operation = operator_operation::mixed_subtraction;
		}
		elseif ($source_operator === operator_kind::multiplication) {
			$decision->operation = operator_operation::mixed_multiplication;
		}
		elseif ($source_operator === operator_kind::division) {
			$decision->operation = operator_operation::mixed_division;
		}
		elseif ($source_operator === operator_kind::remainder) {
			$decision->operation = operator_operation::mixed_remainder;
		}
		else {
			throw new \RuntimeException('S2S mixed operator is outside the numeric arithmetic slice');
		}
		if (q_count($operands) !== ($unary ? 1 : 2)) {
			throw new \LogicException('Mixed arithmetic operand count mismatch');
		}
		$box = $operands[0];
		if (!Mixed_Conversions::is_mixed($box)) {
			$box = $operands[1];
		}
		$decision->result_type = $box;
		foreach ($operands as $operand)
		{
			if (!self::is_numeric_operand($operand)) {
				throw new \RuntimeException('S2S mixed arithmetic requires matching mixed, ordinary int or float operands');
			}
			if ($source_operator === operator_kind::remainder) {
				if (!Mixed_Conversions::is_mixed($operand) && !$operand->matches(Language_Types::integer(Model::$language_scope))) {
					throw new \RuntimeException('S2S mixed remainder requires mixed or ordinary int operands');
				}
			}
			$decision->operands[] = Conversion_Preparation::decide($operand, $box, conversion_context::operator_operand);
		}
		return $decision;
	}

	/** Ordering has a numeric domain; equality and strict identity have separate policies. */
	private static function is_comparison(operator_kind $source_operator): bool
	{
		return ($source_operator === operator_kind::equal) || ($source_operator === operator_kind::not_equal)
			|| ($source_operator === operator_kind::identical) || ($source_operator === operator_kind::not_identical)
			|| ($source_operator === operator_kind::less) || ($source_operator === operator_kind::less_equal)
			|| ($source_operator === operator_kind::greater) || ($source_operator === operator_kind::greater_equal);
	}

	private static function is_numeric_operand(canonical_type_use $operand): bool
	{
		return !$operand->by_value() && (Mixed_Conversions::is_mixed($operand)
			|| $operand->matches(Language_Types::integer(Model::$language_scope))
			|| $operand->matches(Language_Types::floating(Model::$language_scope)));
	}

	/** Keep literal null unboxed for strict identity: its helper normalizes empty handles. */
	private static function decide_comparison(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Mixed comparison requires two operands');
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = Language_Types::boolean(Model::$language_scope);
		$strict = ($source_operator === operator_kind::identical) || ($source_operator === operator_kind::not_identical);
		$equality = $strict || ($source_operator === operator_kind::equal) || ($source_operator === operator_kind::not_equal);
		if ($source_operator === operator_kind::equal) {
			$decision->operation = operator_operation::mixed_equal;
		}
		elseif ($source_operator === operator_kind::not_equal) {
			$decision->operation = operator_operation::mixed_not_equal;
		}
		elseif ($source_operator === operator_kind::identical) {
			$decision->operation = operator_operation::mixed_identical;
		}
		elseif ($source_operator === operator_kind::not_identical) {
			$decision->operation = operator_operation::mixed_not_identical;
		}
		elseif ($source_operator === operator_kind::less) {
			$decision->operation = operator_operation::mixed_less;
		}
		elseif ($source_operator === operator_kind::less_equal) {
			$decision->operation = operator_operation::mixed_less_equal;
		}
		elseif ($source_operator === operator_kind::greater) {
			$decision->operation = operator_operation::mixed_greater;
		}
		else {
			$decision->operation = operator_operation::mixed_greater_equal;
		}
		$box = $operands[0];
		if (!Mixed_Conversions::is_mixed($box)) {
			$box = $operands[1];
		}
		foreach ($operands as $operand)
		{
			if ($operand->by_value()) {
				throw new \RuntimeException('S2S mixed comparison does not support storage modifiers');
			}
			if ($equality) {
				if (!$operand->matches($box) && !Mixed_Conversions::is_scalar_payload($operand) && !Nullable_Conversions::is_null($operand)) {
					throw new \RuntimeException('S2S mixed equality requires matching mixed, scalar or null operands');
				}
			}
			elseif (!self::is_numeric_operand($operand)) {
				throw new \RuntimeException('S2S mixed ordering requires mixed or ordinary numeric operands');
			}
			if ($strict) {
				$decision->operands[] = Conversion_Preparation::operation($operand, $operand,
					conversion_context::operator_operand, conversion_operation::identity);
			}
			else {
				$decision->operands[] = Conversion_Preparation::decide($operand, $box, conversion_context::operator_operand);
			}
		}
		return $decision;
	}
}
