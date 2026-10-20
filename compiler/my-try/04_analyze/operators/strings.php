<?php

/* Exact byte comparisons; text coercion and carrier lifting are separate domains. */
namespace scpp\compiler;

final class String_Operators
{
	public static function is_comparison(operator_kind $source_operator): bool
	{
		return ($source_operator === operator_kind::equal) || ($source_operator === operator_kind::not_equal)
			|| ($source_operator === operator_kind::less) || ($source_operator === operator_kind::less_equal)
			|| ($source_operator === operator_kind::greater) || ($source_operator === operator_kind::greater_equal)
			|| ($source_operator === operator_kind::three_way);
	}

	/** Select exact string operands without numeric or text conversion. */
	public static function decide_comparison(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if ((q_count($operands) !== 2) || !self::is_comparison($source_operator)) {
			throw new \LogicException('Unsupported string comparison shape');
		}

		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $source_operator === operator_kind::three_way ? $context->integer : $context->boolean;
		if ($source_operator === operator_kind::equal) {
			$decision->operation = operator_operation::string_equal;
		}
		elseif ($source_operator === operator_kind::not_equal) {
			$decision->operation = operator_operation::string_not_equal;
		}
		elseif ($source_operator === operator_kind::less) {
			$decision->operation = operator_operation::string_less;
		}
		elseif ($source_operator === operator_kind::less_equal) {
			$decision->operation = operator_operation::string_less_equal;
		}
		elseif ($source_operator === operator_kind::greater) {
			$decision->operation = operator_operation::string_greater;
		}
		elseif ($source_operator === operator_kind::greater_equal) {
			$decision->operation = operator_operation::string_greater_equal;
		}
		elseif ($source_operator === operator_kind::three_way) {
			$decision->operation = operator_operation::string_three_way;
		}

		foreach ($operands as $operand)
		{
			if (!String_Conversions::is_string($operand)) {
				throw new \RuntimeException('S2S string comparison requires exact string operands');
			}
			$decision->operands[] = Conversion_Preparation::decide(
				$operand, $context->string_type, conversion_context::operator_operand);
		}
		return $decision;
	}
}
