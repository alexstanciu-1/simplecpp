<?php

/* Select canonical floating arithmetic and its candidate-specific numeric domain. */
namespace scpp\compiler;

final class Floating_Point_Operators
{
	public static function is_arithmetic(operator_kind $source_operator): bool
	{
		return ($source_operator === operator_kind::addition) || ($source_operator === operator_kind::subtraction)
			|| ($source_operator === operator_kind::multiplication) || ($source_operator === operator_kind::division);
	}

	/** Float destinations admit canonical int RHS promotion; narrowing stays separate. */
	public static function require_compound_operands(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */): void
	{
		$floating = Language_Types::floating(Model::$language_scope);
		if ((q_count($operands) !== 2) || !self::is_arithmetic($source_operator)
			|| !$operands[0]->matches($floating)
			|| (!$operands[1]->matches($floating) && !$operands[1]->matches(Language_Types::integer(Model::$language_scope)))) {
			throw new \RuntimeException('S2S floating compounds require a float destination, float or canonical int RHS and arithmetic operators');
		}
	}

	public static function is_ordering(operator_kind $source_operator): bool
	{
		return ($source_operator === operator_kind::less) || ($source_operator === operator_kind::less_equal)
			|| ($source_operator === operator_kind::greater) || ($source_operator === operator_kind::greater_equal);
	}

	public static function is_comparison(operator_kind $source_operator): bool
	{
		return ($source_operator === operator_kind::equal) || ($source_operator === operator_kind::not_equal)
			|| self::is_ordering($source_operator) || ($source_operator === operator_kind::three_way);
	}

	/** The canonical mixed numeric pair shared by scalar and state-aware comparisons. */
	public static function is_mixed_numeric_pair(canonical_type_use $left,
		canonical_type_use $right, preparation_context $context): bool
	{
		$floating = Language_Types::floating(Model::$language_scope);
		return ($left->matches($context->integer) && $right->matches($floating))
			|| ($left->matches($floating) && $right->matches($context->integer));
	}

	/** Numeric comparisons promote canonical int operands to float. */
	public static function decide_comparison(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if ((q_count($operands) !== 2) || !self::is_comparison($source_operator)) {
			throw new \LogicException('Unsupported floating-point comparison shape');
		}
		$floating = Language_Types::floating(Model::$language_scope);
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $source_operator === operator_kind::three_way ? $context->integer : $context->boolean;
		if ($source_operator === operator_kind::three_way) {
			$decision->operation = operator_operation::floating_three_way;
		}
		elseif ($source_operator === operator_kind::equal) {
			$decision->operation = operator_operation::floating_equal;
		}
		elseif ($source_operator === operator_kind::not_equal) {
			$decision->operation = operator_operation::floating_not_equal;
		}
		elseif ($source_operator === operator_kind::less) {
			$decision->operation = operator_operation::floating_less;
		}
		elseif ($source_operator === operator_kind::less_equal) {
			$decision->operation = operator_operation::floating_less_equal;
		}
		elseif ($source_operator === operator_kind::greater) {
			$decision->operation = operator_operation::floating_greater;
		}
		elseif ($source_operator === operator_kind::greater_equal) {
			$decision->operation = operator_operation::floating_greater_equal;
		}
		foreach ($operands as $operand) {
			if (!$operand->matches($floating) && !$operand->matches($context->integer)) {
				throw new \RuntimeException('S2S floating comparison requires canonical float or int operands');
			}
			$decision->operands[] = Conversion_Preparation::decide(
				$operand, $floating, conversion_context::operator_operand);
		}
		return $decision;
	}

	public static function decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		$unary = ($source_operator === operator_kind::unary_plus) || ($source_operator === operator_kind::unary_minus);
		if ((q_count($operands) !== ($unary ? 1 : 2)) || (!$unary && !self::is_arithmetic($source_operator))) {
			throw new \LogicException('Unsupported floating-point arithmetic shape');
		}
		$floating = Language_Types::floating(Model::$language_scope);
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $floating;
		if ($source_operator === operator_kind::unary_plus) {
			$decision->operation = operator_operation::floating_positive;
		}
		elseif ($source_operator === operator_kind::unary_minus) {
			$decision->operation = operator_operation::floating_negative;
		}
		elseif ($source_operator === operator_kind::addition) {
			$decision->operation = operator_operation::floating_addition;
		}
		elseif ($source_operator === operator_kind::subtraction) {
			$decision->operation = operator_operation::floating_subtraction;
		}
		elseif ($source_operator === operator_kind::multiplication) {
			$decision->operation = operator_operation::floating_multiplication;
		}
		else {
			$decision->operation = operator_operation::floating_division;
		}
		foreach ($operands as $operand) {
			if (!$operand->matches($floating) && ($unary || !$operand->matches($context->integer))) {
				throw new \RuntimeException('S2S floating arithmetic requires canonical float or int operands');
			}
			$decision->operands[] = Conversion_Preparation::decide(
				$operand, $floating, conversion_context::operator_operand);
		}
		return $decision;
	}
}
