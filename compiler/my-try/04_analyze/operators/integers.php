<?php

/* Select hard-coded language operators for canonical integers. */
namespace scpp\compiler;

final class Integer_Operators
{
	/** Select canonical-int arithmetic operations through one exact operand/conversion policy. */
	public static function decide_arithmetic(operator_kind $source_operator,
	array $operands /** vector<canonical_type_use> */,
	preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Integer arithmetic operation requires two operands');
		}
		$integer = $context->integer;
		if ((!$operands[0]->matches($integer)) || (!$operands[1]->matches($integer))) {
			throw new \RuntimeException('S2S integer arithmetic operation requires canonical int operands');
		}

		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		if ($source_operator === operator_kind::addition) {
			$decision->operation = operator_operation::integer_addition;
		}
		elseif ($source_operator === operator_kind::subtraction) {
			$decision->operation = operator_operation::integer_subtraction;
		}
		elseif ($source_operator === operator_kind::multiplication) {
			$decision->operation = operator_operation::integer_multiplication;
		}
		else {
			throw new \LogicException('Unsupported integer arithmetic operation');
		}
		$decision->operands[] = Conversion_Preparation::decide(
		$operands[0], $integer, conversion_context::operator_operand);
		$decision->operands[] = Conversion_Preparation::decide(
		$operands[1], $integer, conversion_context::operator_operand);
		$decision->result_type = $integer;
		return $decision;
	}
}
