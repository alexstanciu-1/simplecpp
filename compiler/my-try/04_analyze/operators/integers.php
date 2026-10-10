<?php

/* Select language integer operators from registered width and signedness facts. */
namespace scpp\compiler;

final class Integer_Operators
{
	/** Select binary operations and retain the common operand domain separately from the result. */
	public static function decide_binary(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */,
		preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Integer binary operation requires two operands');
		}
		$integer = self::operand_domain($source_operator, $operands[0], $operands[1], $context);

		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $integer;
		if ($source_operator === operator_kind::addition) {
			$decision->operation = operator_operation::integer_addition;
		}
		elseif ($source_operator === operator_kind::subtraction) {
			$decision->operation = operator_operation::integer_subtraction;
		}
		elseif ($source_operator === operator_kind::power) {
			$decision->operation = operator_operation::integer_power;
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
		elseif ($source_operator === operator_kind::bitwise_and) {
			$decision->operation = operator_operation::integer_bitwise_and;
		}
		elseif ($source_operator === operator_kind::bitwise_or) {
			$decision->operation = operator_operation::integer_bitwise_or;
		}
		elseif ($source_operator === operator_kind::bitwise_xor) {
			$decision->operation = operator_operation::integer_bitwise_xor;
		}
		elseif ($source_operator === operator_kind::shift_left) {
			$decision->operation = operator_operation::integer_shift_left;
		}
		elseif ($source_operator === operator_kind::shift_right) {
			$decision->operation = operator_operation::integer_shift_right;
		}
		elseif ($source_operator === operator_kind::three_way) {
			$decision->operation = operator_operation::integer_three_way;
			$decision->result_type = $context->integer;
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

	/** Only ordinary arithmetic and numeric comparisons admit same-signedness widening. */
	private static function operand_domain(operator_kind $source_operator,
		canonical_type_use $left, canonical_type_use $right,
		preparation_context $context): canonical_type_use
	{
		$widens = ($source_operator === operator_kind::addition)
			|| ($source_operator === operator_kind::subtraction)
			|| ($source_operator === operator_kind::multiplication)
			|| ($source_operator === operator_kind::division)
			|| ($source_operator === operator_kind::remainder)
			|| ($source_operator === operator_kind::equal)
			|| ($source_operator === operator_kind::not_equal)
			|| ($source_operator === operator_kind::less)
			|| ($source_operator === operator_kind::less_equal)
			|| ($source_operator === operator_kind::greater)
			|| ($source_operator === operator_kind::greater_equal)
			|| ($source_operator === operator_kind::three_way);
		if (!$widens) {
			if (!$left->matches($context->integer) || !$right->matches($context->integer)) {
				throw new \RuntimeException('S2S integer binary operation requires canonical int operands');
			}
			return $context->integer;
		}
		$left_type = Type_Queries::canonical($left);
		$right_type = Type_Queries::canonical($right);
		if (!($left_type instanceof integer_type) || !($right_type instanceof integer_type)) {
			throw new \RuntimeException('S2S integer binary operation requires integer operands');
		}
		$left_definition = object_cast($left_type, integer_type::class)->integer_definition();
		$right_definition = object_cast($right_type, integer_type::class)->integer_definition();
		if ($left_definition->signed() !== $right_definition->signed()) {
			throw new \RuntimeException('S2S integer binary operation requires matching signedness; use an explicit conversion');
		}
		if ($left->matches($context->integer) || $right->matches($context->integer)) {
			return $context->integer;
		}
		if ($left_definition->bit_width() >= $right_definition->bit_width()) {
			return $left;
		}
		return $right;
	}
}
