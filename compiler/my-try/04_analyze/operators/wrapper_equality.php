<?php

/* Wrapper equality compares saved states; it never requests guarded payload extraction. */
namespace scpp\compiler;

final class Wrapper_Equality
{
	public static function try_decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, operator_context $operator_context,
		preparation_context $context): ?operator_decision
	{
		if (($source_operator !== operator_kind::equal) && ($source_operator !== operator_kind::not_equal)
			&& ($source_operator !== operator_kind::identical) && ($source_operator !== operator_kind::not_identical)) {
			return null;
		}
		if (q_count($operands) !== 2) {
			return null;
		}
		$left_payload = self::payload($operands[0]);
		$right_payload = self::payload($operands[1]);
		if (($left_payload === null) && ($right_payload === null)) {
			return null;
		}
		// Strict identity normalizes visible values across carriers; ordinary equality
		// retains its same-carrier/plain domain. Both paths snapshot whole states.
		if (($left_payload !== null) && ($right_payload !== null)
			&& !$operands[0]->matches($operands[1])
			&& ($source_operator !== operator_kind::identical) && ($source_operator !== operator_kind::not_identical)) {
			return null;
		}
		$left_value = $left_payload ?? $operands[0];
		$right_value = $right_payload ?? $operands[1];
		$strict_identity = ($source_operator === operator_kind::identical) || ($source_operator === operator_kind::not_identical);
		$admitted_pair = $left_value->matches($right_value)
			|| ($strict_identity && Scalar_Identity::is_distinct_kind_pair($left_value, $right_value, $context));
		if (!$admitted_pair || !self::is_admitted_payload($left_value, $context)
			|| $operands[0]->by_value() || $operands[1]->by_value()) {
			return null;
		}
		if ($operator_context !== operator_context::expression) {
			throw new \RuntimeException('S2S wrapper equality requires a value expression');
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $context->boolean;
		if ($source_operator === operator_kind::equal) {
			$decision->operation = operator_operation::wrapper_equal;
		}
		elseif ($source_operator === operator_kind::not_equal) {
			$decision->operation = operator_operation::wrapper_not_equal;
		}
		elseif ($source_operator === operator_kind::identical) {
			$decision->operation = operator_operation::wrapper_identical;
		}
		else {
			$decision->operation = operator_operation::wrapper_not_identical;
		}
		foreach ($operands as $operand)
		{
			// Identity conversion snapshots the complete carrier, including absence or a structured error.
			$decision->operands[] = Conversion_Preparation::operation($operand, $operand,
				conversion_context::operator_operand, conversion_operation::identity);
		}
		return $decision;
	}
	/** Only roles whose comparison contracts are admitted here participate. */
	private static function payload(canonical_type_use $source): ?canonical_type_use
	{
		if ($source->by_value()) {
			return null;
		}
		$nullable = Nullable_Conversions::payload($source);
		if ($nullable !== null) {
			return $nullable;
		}
		$role = Result_Conversions::role($source);
		if ($role !== null) {
			if ($role->result_family() === result_kind::structured_error) {
				return Result_Conversions::require_payload($source, $role);
			}
			// Sentinel equality preserves complete states for matching canonical payloads.
			if (($role->result_family() === result_kind::false_sentinel)
				|| ($role->result_family() === result_kind::boolean_sentinels)) {
				return Result_Conversions::require_payload($source, $role);
			}
		}
		return null;
	}
	/** Canonical payload capabilities; the pair rule separately controls kind compatibility. */
	private static function is_admitted_payload(canonical_type_use $payload, preparation_context $context): bool
	{
		return $payload->matches($context->integer) || $payload->matches($context->boolean)
			|| $payload->matches($context->string_type)
			|| $payload->matches(Language_Types::floating(Model::$language_scope));
	}
}
