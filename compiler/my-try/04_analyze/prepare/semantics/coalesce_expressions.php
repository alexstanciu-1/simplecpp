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
			$selected_type = $left->type;
			$facts->presence = Boxed_Conversions::is_boxed($left->type)
				? coalesce_presence::boxed_nonnull : coalesce_presence::always;
			$payload = Nullable_Conversions::payload($selected_type);
			if ($payload !== null) {
				$selected_type = $payload;
				$facts->presence = coalesce_presence::wrapper_value;
			}
			$role = Result_Conversions::role($left->type);
			if ($role !== null) {
				$selected_type = Result_Conversions::require_payload($left->type, $role);
				$facts->presence = coalesce_presence::wrapper_value;
			}
			$relationships = new Conversion_Relationships($context);
			if ((Ownership_Conversions::role($left->type) !== null) || (Ownership_Conversions::role($fallback->type) !== null)) {
				$facts->type = Selection_Result_Preparation::shared_join($left->type, $fallback->type, $context);
				$facts->presence = coalesce_presence::handle_presence;
				$facts->fallback_conversion = Conversion_Preparation::decide($fallback->type, $facts->type, conversion_context::conditional_branch, $relationships);
			}
			elseif (Boxed_Conversions::is_boxed($selected_type) || Boxed_Conversions::is_boxed($fallback->type)) {
				$facts->type = Selection_Result_Preparation::boxed_join($selected_type, $fallback->type, $context);
				$facts->fallback_conversion = Conversion_Preparation::decide($fallback->type, $facts->type, conversion_context::conditional_branch);
			}
			elseif ((($payload !== null) || ($role !== null)) && $left->type->matches($fallback->type)) {
				self::require_canonical_payload($selected_type);
				Capability_Preparation::require_copy($selected_type, $context);
				Capability_Preparation::require_copy($fallback->type, $context);
				$facts->type = $selected_type;
				$facts->fallback_conversion = Conversion_Preparation::operation($fallback->type, $selected_type,
					conversion_context::coalesce_value, conversion_operation::coalesce_selected_value);
			}
			elseif ($payload !== null) {
				$facts->type = Selection_Result_Preparation::nullable_value_join($selected_type, $fallback->type, $context);
				$facts->fallback_conversion = Conversion_Preparation::decide($fallback->type, $facts->type, conversion_context::conditional_branch);
			}
			elseif ($role !== null) {
				// Exact Result payload agreement also admits callable contracts, without widening direct joins.
				if (!$selected_type->matches($fallback->type)) {
					throw new \RuntimeException('S2S coalesce branches require matching scalar types or exact Result payload types');
				}
				Capability_Preparation::require_copy($selected_type, $context);
				$facts->type = $selected_type;
				$facts->fallback_conversion = Conversion_Preparation::decide($fallback->type, $facts->type, conversion_context::conditional_branch);
			}
			else {
				// Coalescing has not admitted enum payloads/results; shared direct joins have.
				if (!Conversion_Preparation::is_scalar($selected_type) || !Conversion_Preparation::is_scalar($fallback->type)) {
					throw new \RuntimeException('S2S coalesce branches require supported scalar value types');
				}
				$facts->type = Selection_Result_Preparation::begin($selected_type, $context, 'coalesce');
				$facts->fallback_conversion = Selection_Result_Preparation::branch($fallback->type, $facts->type, 'coalesce');
			}
			Capability_Preparation::require_copy($left->type, $context);
			$facts->left_extraction = Conversion_Preparation::decide($left->type, $selected_type, conversion_context::coalesce_value);
			$facts->left_conversion = Conversion_Preparation::decide($selected_type, $facts->type, conversion_context::coalesce_value, $relationships);
			return $facts;
		}
		finally { $context->statement_assignment = $statement_assignment; }
	}

	/** This pair slice does not widen records, boxed payloads or fixed-width domains. */
	private static function require_canonical_payload(canonical_type_use $payload): void
	{
		$scope = Model::$language_scope;
		if (!$payload->matches(Language_Types::integer($scope)) && !$payload->matches(Language_Types::floating($scope))
			&& !$payload->matches(Language_Types::boolean($scope)) && !$payload->matches(Language_Types::string_type($scope))) {
			throw new \RuntimeException('Wrapper coalescing requires an exact canonical scalar payload');
		}
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
