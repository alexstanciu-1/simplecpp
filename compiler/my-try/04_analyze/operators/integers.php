<?php

/* Select hard-coded language operators for canonical integers. */
namespace scpp\compiler;

final class Integer_Operators
{
	/** Select canonical-int binary operations through one exact operand/conversion policy. */
	public static function decide_binary(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */,
		preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Integer binary operation requires two operands');
		}
		$integer = $context->integer;
		if ((!$operands[0]->matches($integer)) || (!$operands[1]->matches($integer))) {
			throw new \RuntimeException('S2S integer binary operation requires canonical int operands');
		}

		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $integer;
		if ($source_operator === operator_kind::addition) {
			$decision->operation = operator_operation::integer_addition;
		}
		elseif ($source_operator === operator_kind::subtraction) {
			$decision->operation = operator_operation::integer_subtraction;
		}
		elseif ($source_operator === operator_kind::multiplication) {
			$decision->operation = operator_operation::integer_multiplication;
		}
		elseif ($source_operator === operator_kind::division) {
			$decision->operation = operator_operation::integer_division;
		}
		elseif ($source_operator === operator_kind::remainder) {
			$decision->operation = operator_operation::integer_remainder;
		}
		elseif ($source_operator === operator_kind::three_way) {
			$decision->operation = operator_operation::integer_three_way;
		}
		else
		{
			$decision->result_type = $context->boolean;
			if ($source_operator === operator_kind::equal) {
				$decision->operation = operator_operation::integer_equal;
			}
			elseif ($source_operator === operator_kind::not_equal) {
				$decision->operation = operator_operation::integer_not_equal;
			}
			elseif ($source_operator === operator_kind::identical) {
				$decision->operation = operator_operation::integer_identical;
			}
			elseif ($source_operator === operator_kind::not_identical) {
				$decision->operation = operator_operation::integer_not_identical;
			}
			elseif ($source_operator === operator_kind::less) {
				$decision->operation = operator_operation::integer_less;
			}
			elseif ($source_operator === operator_kind::less_equal) {
				$decision->operation = operator_operation::integer_less_equal;
			}
			elseif ($source_operator === operator_kind::greater) {
				$decision->operation = operator_operation::integer_greater;
			}
			elseif ($source_operator === operator_kind::greater_equal) {
				$decision->operation = operator_operation::integer_greater_equal;
			}
			else {
				throw new \LogicException('Unsupported integer binary operation');
			}
		}
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[0], $integer, conversion_context::operator_operand);
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[1], $integer, conversion_context::operator_operand);
		return $decision;
	}
}
