<?php

/* Select exact boolean equality without numeric or truthiness coercion. */
namespace scpp\compiler;

final class Boolean_Operators
{
	public static function decide_equality(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if ((q_count($operands) !== 2)
			|| (($source_operator !== operator_kind::equal) && ($source_operator !== operator_kind::not_equal))) {
			throw new \LogicException('Boolean equality requires two operands and an equality operator');
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->operation = $source_operator === operator_kind::equal
			? operator_operation::boolean_equal : operator_operation::boolean_not_equal;
		$decision->result_type = $context->boolean;
		foreach ($operands as $operand) {
			if (!$operand->matches($context->boolean)) {
				throw new \RuntimeException('S2S boolean equality requires exact bool operands');
			}
			$decision->operands[] = Conversion_Preparation::decide(
				$operand, $context->boolean, conversion_context::operator_operand);
		}
		return $decision;
	}
}
