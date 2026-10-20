<?php

/* Checked operator payloads are distinct from wrapper conditions and equality states. */
namespace scpp\compiler;

final class Guarded_Operators
{
	/** Select a bounded extraction stage, then delegate admission to existing payload operators. */
	public static function try_decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, operator_context $operator_context,
		preparation_context $context): ?operator_decision
	{
		if (!self::supports($source_operator)) {
			return null;
		}

		$carrier_extraction /** nullable<conversion_decision> */ = null;
		foreach ($operands as $operand)
		{
			$carrier_extraction = self::scalar_extraction($operand, $source_operator, $context);
			if ($carrier_extraction !== null) {
				break;
			}
		}
		if ($carrier_extraction === null) {
			return null;
		}

		$extractions /** vector<conversion_decision> */ = [];
		$payloads /** vector<canonical_type_use> */ = [];
		foreach ($operands as $operand)
		{
			$extraction = self::scalar_extraction($operand, $source_operator, $context);
			if ($extraction !== null)
			{
				$extractions[] = $extraction;
				$payloads[] = $extraction->target_type;
			}
			else
			{
				if (!self::is_admitted_payload($operand, $source_operator, $context)) {
					throw new \RuntimeException('S2S guarded operators require independently extractable or plain admitted scalar operands');
				}
				$extractions[] = Conversion_Preparation::operation($operand, $operand,
					conversion_context::operator_operand, conversion_operation::identity);
				$payloads[] = $operand;
			}
		}

		if ($operator_context === operator_context::compound_assignment) {
			self::require_compound_admission($source_operator, $payloads, $context);
		}

		// All inputs now have ordinary payload types, so this cannot recursively lift.
		$decision = Operator_Preparation::decide($source_operator, $payloads, operator_context::expression, $context);
		$decision->operand_extractions = $extractions;
		return $decision;
	}

	/** Compound admission is bounded independently of ordinary guarded expressions. */
	private static function require_compound_admission(operator_kind $source_operator,
		array $payloads /** vector<canonical_type_use> */,
		preparation_context $context): void
	{
		$floating = Language_Types::floating(Model::$language_scope);
		if (q_count($payloads) === 2) {
			if ($payloads[0]->matches($floating) || $payloads[1]->matches($floating)) {
				Floating_Point_Operators::require_compound_operands($source_operator, $payloads);
				return;
			}
		}

		if (($source_operator !== operator_kind::addition) && ($source_operator !== operator_kind::subtraction)
			&& ($source_operator !== operator_kind::multiplication) && ($source_operator !== operator_kind::division)
			&& ($source_operator !== operator_kind::remainder) && ($source_operator !== operator_kind::bitwise_and)
			&& ($source_operator !== operator_kind::bitwise_or) && ($source_operator !== operator_kind::bitwise_xor)
			&& ($source_operator !== operator_kind::shift_left) && ($source_operator !== operator_kind::shift_right)
			&& ($source_operator !== operator_kind::power)) {
			throw new \RuntimeException('S2S guarded compound operator remains deferred');
		}
		if ((q_count($payloads) !== 2) || !$payloads[0]->matches($context->integer)
			|| !$payloads[1]->matches($context->integer)) {
			throw new \RuntimeException('S2S guarded compounds require canonical int payloads');
		}
	}

	/** Carrier roles grant extraction; source spellings and implicit boundary conversions do not. */
	private static function scalar_extraction(canonical_type_use $source,
		operator_kind $source_operator, preparation_context $context): ?conversion_decision
	{
		if ($source->by_value()) {
			return null;
		}
		$nullable_payload = Nullable_Conversions::payload($source);
		if ($nullable_payload !== null)
		{
			if (self::is_admitted_payload($nullable_payload, $source_operator, $context)) {
				return Conversion_Preparation::operation($source, $nullable_payload,
					conversion_context::operator_operand, conversion_operation::nullable_extract);
			}
			return null;
		}
		$result_role = Result_Conversions::role($source);
		if ($result_role !== null)
		{
			$payload = Result_Conversions::require_payload($source, $result_role);
			if (self::is_admitted_payload($payload, $source_operator, $context)) {
				return Conversion_Preparation::operation($source, $payload,
					conversion_context::operator_operand, conversion_operation::result_extract);
			}
		}
		return null;
	}

	/** Payload admission is bounded; the scalar owner still decides applicable operators. */
	private static function is_admitted_payload(canonical_type_use $payload,
		operator_kind $source_operator, preparation_context $context): bool
	{
		// Spaceship admits canonical numbers and strings, with scalar-owned ordering rules.
		if ($source_operator === operator_kind::three_way) {
			return $payload->matches($context->integer) || $payload->matches($context->string_type)
				|| $payload->matches(Language_Types::floating(Model::$language_scope));
		}
		// String ordering must not redirect unrelated nullable-string condition negation.
		if ($payload->matches($context->string_type)) {
			return ($source_operator === operator_kind::less) || ($source_operator === operator_kind::less_equal)
				|| ($source_operator === operator_kind::greater) || ($source_operator === operator_kind::greater_equal);
		}
		return $payload->matches($context->boolean)
			|| $payload->matches($context->integer)
			|| $payload->matches(Language_Types::floating(Model::$language_scope));
	}

	/** Equality normalizes states; mutation needs a writable carrier, neither is value lifting. */
	private static function supports(operator_kind $source_operator): bool
	{
		return ($source_operator === operator_kind::unary_plus) || ($source_operator === operator_kind::unary_minus)
			|| ($source_operator === operator_kind::bitwise_not) || ($source_operator === operator_kind::logical_not)
			|| ($source_operator === operator_kind::addition) || ($source_operator === operator_kind::subtraction)
			|| ($source_operator === operator_kind::multiplication) || ($source_operator === operator_kind::division)
			|| ($source_operator === operator_kind::remainder) || ($source_operator === operator_kind::power)
			|| ($source_operator === operator_kind::less) || ($source_operator === operator_kind::less_equal)
			|| ($source_operator === operator_kind::greater) || ($source_operator === operator_kind::greater_equal)
			|| ($source_operator === operator_kind::bitwise_and) || ($source_operator === operator_kind::bitwise_or)
			|| ($source_operator === operator_kind::bitwise_xor) || ($source_operator === operator_kind::shift_left)
			|| ($source_operator === operator_kind::shift_right) || ($source_operator === operator_kind::logical_and)
			|| ($source_operator === operator_kind::logical_or) || ($source_operator === operator_kind::logical_xor) || ($source_operator === operator_kind::three_way);
	}
}
