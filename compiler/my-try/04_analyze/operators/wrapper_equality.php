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
		$left_value = $left_payload ?? $operands[0];
		$right_value = $right_payload ?? $operands[1];
		$strict_identity = ($source_operator === operator_kind::identical) || ($source_operator === operator_kind::not_identical);
		// Identity and ordinary equality own separate pair domains. Both retain whole states.
		$admitted_pair = $strict_identity
			? ($left_value->matches($right_value)
				|| Scalar_Identity::is_distinct_kind_pair($left_value, $right_value, $context))
			: self::ordinary_pair_allowed($operands[0], $operands[1], $left_value, $right_value, $context);
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

	/** Ordinary equality preserves family-specific states, including mixed canonical numbers. */
	private static function ordinary_pair_allowed(canonical_type_use $left, canonical_type_use $right,
		canonical_type_use $left_value, canonical_type_use $right_value, preparation_context $context): bool
	{
		$left_plain = $left->matches($left_value);
		$right_plain = $right->matches($right_value);
		if ($left_value->matches($right_value)) {
			// Cross-family state equality is bounded to matching canonical non-bool payloads.
			return $left_plain || $right_plain || $left->matches($right)
				|| $left_value->matches($context->integer)
				|| $left_value->matches($context->string_type)
				|| $left_value->matches(Language_Types::floating(Model::$language_scope));
		}
		// All supported carrier/plain families now share canonical numeric equality.
		return Floating_Point_Operators::is_mixed_numeric_pair($left_value, $right_value, $context);
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
