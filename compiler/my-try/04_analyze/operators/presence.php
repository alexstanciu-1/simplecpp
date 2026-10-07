<?php

/* Strict null comparisons inspect optional/owning-handle presence, never pointee truthiness. */
namespace scpp\compiler;

final class Presence_Operators
{
	public static function decide_null_comparison(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if (($source_operator !== operator_kind::identical) && ($source_operator !== operator_kind::not_identical)) {
			throw new \RuntimeException('S2S null comparison requires === or !==');
		}
		$value_index = 0;
		if (Nullable_Conversions::is_null($operands[0])) { $value_index = 1; }
		$type = $operands[$value_index];
		if (Ownership_Conversions::role($type) !== null) {
			Ownership_Conversions::require_presence($type);
		}
		else {
			$payload = Nullable_Conversions::payload($type);
			if (($payload === null) || $type->by_value()) {
				throw new \RuntimeException('S2S null comparison requires a nullable value or owning handle');
			}
			Nullable_Conversions::require_value_payload($payload);
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $context->boolean;
		if ($source_operator === operator_kind::identical) {
			$decision->operation = operator_operation::presence_is_null;
		} else {
			$decision->operation = operator_operation::presence_not_null;
		}
		foreach ($operands as $operand) {
			$decision->operands[] = Conversion_Preparation::operation($operand, $operand,
				conversion_context::operator_operand, conversion_operation::identity);
		}
		return $decision;
	}
}
