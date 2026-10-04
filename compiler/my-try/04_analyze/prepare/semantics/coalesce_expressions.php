<?php

/* Prepared presence and selected-value domains; fallback evaluation stays lazy. */
namespace scpp\compiler;

final class Coalesce_Expression_Preparation
{
	/** Validate both alternatives and attach presence independently of branch normalization. */
	public static function prepare(coalesce_expression_node $syntax, preparation_context $context): prepared_coalesce_expression
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try
		{
			$left = Expression_Preparation::prepare($syntax->left, $context);
			$fallback = Expression_Preparation::prepare($syntax->fallback, $context);
			self::require_admitted_family($left->type);
			self::require_admitted_family($fallback->type);
			$facts = new prepared_coalesce_expression();
			if (Mixed_Conversions::is_mixed($left->type) || Mixed_Conversions::is_mixed($fallback->type))
			{
				$facts->type = Selection_Result_Preparation::mixed_join($left->type, $fallback->type, $context);
				$facts->presence = Mixed_Conversions::is_mixed($left->type)
					? coalesce_presence::mixed_nonnull : coalesce_presence::always;
				$facts->left_conversion = Conversion_Preparation::decide($left->type, $facts->type, conversion_context::coalesce_value);
				$facts->fallback_conversion = Conversion_Preparation::decide($fallback->type, $facts->type, conversion_context::conditional_branch);
				return $facts;
			}
			$result = $left->type;
			$facts->presence = coalesce_presence::always;
			$payload = Nullable_Conversions::payload($result);
			if ($payload !== null) {
				$result = $payload;
				$facts->presence = coalesce_presence::wrapper_value;
			}
			$role = Result_Conversions::role($left->type);
			if ($role !== null) {
				$result = Result_Conversions::require_scalar_payload($left->type, $role);
				$facts->presence = coalesce_presence::wrapper_value;
				Capability_Preparation::require_copy($left->type, $context);
			}
			$facts->type = Selection_Result_Preparation::begin($result, $context, 'coalesce');
			$facts->fallback_conversion = Selection_Result_Preparation::branch($fallback->type, $result, 'coalesce');
			$facts->left_conversion = Conversion_Preparation::decide($left->type, $result, conversion_context::coalesce_value);
			return $facts;
		}
		finally { $context->statement_assignment = $statement_assignment; }
	}

	/** This family is invalid for coalescing, not merely outside this scalar slice. */
	private static function require_admitted_family(canonical_type_use $type): void
	{
		$role = Result_Conversions::role($type);
		if ($role !== null) {
			if ($role->result_family() === result_kind::boolean_sentinels) {
				throw new \RuntimeException('S2S coalesce does not permit result_or_bool on either side');
			}
		}
	}
}
