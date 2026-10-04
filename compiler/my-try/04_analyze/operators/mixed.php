<?php

/* Mixed arithmetic selects runtime numeric dispatch, never scalar extraction. */
namespace scpp\compiler;

final class Mixed_Operators
{
	/** Numeric payload kinds are checked at runtime; known nonnumeric peers reject here. */
	public static function decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */): operator_decision
	{
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
			if ($source_operator === operator_kind::remainder) {
				if (!Mixed_Conversions::is_mixed($operand) && !$operand->matches(Language_Types::integer(Model::$language_scope))) {
					throw new \RuntimeException('S2S mixed remainder requires mixed or ordinary int operands');
				}
			}
			$decision->operands[] = Conversion_Preparation::decide($operand, $box, conversion_context::operator_operand);
		}
		return $decision;
	}
}
