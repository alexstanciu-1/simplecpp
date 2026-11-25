<?php

/* Strict scalar identity preserves source kinds; it never selects numeric promotion. */
namespace scpp\compiler;

final class Scalar_Identity
{
	/** Admitted distinct scalar kinds: canonical bool, int, float and string; no coercion. */
	public static function is_distinct_kind_pair(canonical_type_use $left,
		canonical_type_use $right, preparation_context $context): bool
	{
		return !$left->matches($right) && self::is_canonical_scalar($left, $context)
			&& self::is_canonical_scalar($right, $context);
	}

	private static function is_canonical_scalar(canonical_type_use $type, preparation_context $context): bool
	{
		return $type->matches($context->integer) || $type->matches($context->boolean)
			|| $type->matches($context->string_type)
			|| $type->matches(Language_Types::floating(Model::$language_scope));
	}

	public static function try_decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): ?operator_decision
	{
		if ((q_count($operands) !== 2)
			|| (($source_operator !== operator_kind::identical) && ($source_operator !== operator_kind::not_identical))) {
			return null;
		}
		$same_string = String_Conversions::is_string($operands[0]) && String_Conversions::is_string($operands[1]);
		$same_bool = $operands[0]->matches($context->boolean) && $operands[1]->matches($context->boolean);
		$floating = Language_Types::floating(Model::$language_scope);
		$same_float = $operands[0]->matches($floating) && $operands[1]->matches($floating);
		if (!$same_string && !$same_bool && !$same_float
			&& !self::is_distinct_kind_pair($operands[0], $operands[1], $context)) {
			return null;
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->operation = $source_operator === operator_kind::identical
			? operator_operation::scalar_identical : operator_operation::scalar_not_identical;
		$decision->result_type = $context->boolean;
		foreach ($operands as $operand) {
			$decision->operands[] = Conversion_Preparation::decide(
				$operand, $operand, conversion_context::operator_operand);
		}
		return $decision;
	}
}

/** Shared identity observes saved handles, independently of optional presence. */
final class Shared_Identity
{
	public static function try_decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): ?operator_decision
	{
		if ((q_count($operands) !== 2)
			|| (($source_operator !== operator_kind::identical) && ($source_operator !== operator_kind::not_identical))) {
			return null;
		}
		$left = self::payload($operands[0]);
		$right = self::payload($operands[1]);
		if (($left === null) && ($right === null)) { return null; }
		if (($left === null) || ($right === null)) {
			throw new \RuntimeException('Shared identity requires two shared object handles or a null comparison');
		}
		if (!$left->matches($right)) {
			$relationships = new Conversion_Relationships($context);
			if (!$relationships->is_subtype($left, $right) && !$relationships->is_subtype($right, $left)) {
				throw new \RuntimeException('Shared identity requires the same payload or compatible base/derived views');
			}
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $context->boolean;
		$decision->operation = $source_operator === operator_kind::identical
			? operator_operation::shared_identical : operator_operation::shared_not_identical;
		foreach ($operands as $operand) {
			$decision->operands[] = Conversion_Preparation::operation($operand, $operand,
				conversion_context::operator_operand, conversion_operation::identity);
		}
		return $decision;
	}

	private static function payload(canonical_type_use $type): ?canonical_type_use
	{
		$observed = Type_Queries::nullable_handle_payload($type) ?? $type;
		$role = Ownership_Conversions::role($observed);
		if (($role === null) || ($role->ownership_family() !== ownership_kind::shared)) { return null; }
		return Ownership_Conversions::payload($observed);
	}
}
