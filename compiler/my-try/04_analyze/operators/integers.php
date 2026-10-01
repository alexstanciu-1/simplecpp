<?php

/* Select hard-coded language operators for canonical integers. */
namespace scpp\compiler;

final class Integer_Operators
{
	/** Preserve the first bounded operator: exact canonical int plus exact canonical int. */
	public static function decide_addition(array $operands /** vector<canonical_type_use> */,
	preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Integer addition requires two operands');
		}
		$integer = $context->integer;
		if ((!$operands[0]->matches($integer)) || (!$operands[1]->matches($integer))) {
			throw new \RuntimeException('S2S integer addition requires canonical int operands');
		}

		$decision = new operator_decision();
		$decision->source_operator = operator_kind::addition;
		$decision->operation = operator_operation::integer_addition;
		$decision->operands[] = Conversion_Preparation::decide(
		$operands[0], $integer, conversion_context::operator_operand);
		$decision->operands[] = Conversion_Preparation::decide(
		$operands[1], $integer, conversion_context::operator_operand);
		$decision->result_type = $integer;
		return $decision;
	}
}
