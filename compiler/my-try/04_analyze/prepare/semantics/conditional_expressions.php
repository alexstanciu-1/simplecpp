<?php

/* Conditional value joins follow the existing matrix, independently of C++ deduction. */
namespace scpp\compiler;

final class Conditional_Expression_Preparation
{
	/** Prepare all alternatives, without giving conditional writes declaration privileges. */
	public static function prepare(conditional_expression_node $syntax, preparation_context $context): prepared_conditional_expression
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try
		{
			$condition = Expression_Preparation::prepare($syntax->condition, $context);
			$facts = new prepared_conditional_expression();
			$facts->truthiness = Condition_Truthiness::decide($condition->type);
			$selected /** prepared_expression */ = $condition;
			if ($syntax->true_branch !== null) {
				$branch /** expression_node */ = $syntax->true_branch;
				$selected = Expression_Preparation::prepare($branch, $context);
			}
			$fallback = Expression_Preparation::prepare($syntax->fallback, $context);
			$facts->type = self::join($selected->type, $fallback->type, $context);
			$facts->true_conversion = Conversion_Preparation::decide($selected->type, $facts->type, conversion_context::conditional_branch);
			$facts->fallback_conversion = Conversion_Preparation::decide($fallback->type, $facts->type, conversion_context::conditional_branch);
			return $facts;
		}
		finally { $context->statement_assignment = $statement_assignment; }
	}

	/** Symmetric conditional join; match and coalescing retain their separate admission rules. */
	private static function join(canonical_type_use $selected, canonical_type_use $fallback,
		preparation_context $context): canonical_type_use
	{
		$selected_domain = self::scalar_domain($selected);
		$fallback_domain = self::scalar_domain($fallback);
		if (!$selected_domain->matches($fallback_domain)) {
			throw new \RuntimeException('S2S conditional branches require matching scalar types or nullable payloads');
		}
		$result = $selected;
		if (Nullable_Conversions::payload($fallback) !== null) { $result = $fallback; }
		Capability_Preparation::require_copy($selected, $context);
		Capability_Preparation::require_copy($fallback, $context);
		Capability_Preparation::require_copy($result, $context);
		return $result;
	}

	/** Bare null and broader carriers do not establish a conditional result domain. */
	private static function scalar_domain(canonical_type_use $type): canonical_type_use
	{
		$payload = Nullable_Conversions::payload($type);
		if ($payload !== null) {
			Nullable_Conversions::require_scalar_payload($payload);
			if ($type->by_value()) { throw new \RuntimeException('S2S nullable conditional branches do not support storage modifiers'); }
			return $payload;
		}
		if (!Conversion_Preparation::is_scalar($type)) {
			throw new \RuntimeException('S2S conditional branches require supported scalar value types or nullable scalars');
		}
		return $type;
	}
}
