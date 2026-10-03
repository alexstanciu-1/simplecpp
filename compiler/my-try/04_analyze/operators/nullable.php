<?php

/* Strict null comparisons inspect optional presence, never payload truthiness. */
namespace scpp\compiler;

final class Nullable_Operators
{
	public static function decide_null_comparison(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if (($source_operator !== operator_kind::identical) && ($source_operator !== operator_kind::not_identical)) {
			throw new \RuntimeException('S2S null comparison requires === or !==');
		}
		$nullable_index = 0;
		if (Nullable_Conversions::is_null($operands[0])) { $nullable_index = 1; }
		$type = $operands[$nullable_index];
		$payload = Nullable_Conversions::payload($type);
		if (($payload === null) || $type->by_value()) {
			throw new \RuntimeException('S2S null comparison requires a nullable value');
		}
		Nullable_Conversions::require_scalar_payload($payload);
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $context->boolean;
		if ($source_operator === operator_kind::identical) {
			$decision->operation = operator_operation::nullable_is_null;
		} else {
			$decision->operation = operator_operation::nullable_not_null;
		}
		foreach ($operands as $operand) {
			$decision->operands[] = Conversion_Preparation::operation($operand, $operand,
				conversion_context::operator_operand, conversion_operation::identity);
		}
		return $decision;
	}
}
