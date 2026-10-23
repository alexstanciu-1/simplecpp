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
			$condition = Expression_Preparation::prepare_for_consumer($syntax->condition, $context, null,
				$syntax->true_branch === null ? expression_consumer::selection_branch : expression_consumer::value);
			$facts = new prepared_conditional_expression();
			$facts->truthiness = Condition_Truthiness::decide($condition->type);
			$paths = Local_Assignment::condition($context);
			$context->pending_locals = $paths->when_true;
			$selected /** prepared_expression */ = $condition;
			if ($syntax->true_branch !== null) {
				$branch /** expression_node */ = $syntax->true_branch;
				$selected = Expression_Preparation::prepare_for_consumer($branch, $context, null, expression_consumer::selection_branch);
			}
			$taken = $context->pending_locals;
			$context->pending_locals = $paths->when_false;
			$fallback = Expression_Preparation::prepare_for_consumer($syntax->fallback, $context, null, expression_consumer::selection_branch);
			$context->pending_locals = Local_Assignment::merge(true, $taken, true, $context->pending_locals);
			$facts->type = self::join($selected->type, $fallback->type, $context);
			$relationships = new Conversion_Relationships($context);
			$facts->true_conversion = Conversion_Preparation::decide($selected->type, $facts->type, conversion_context::conditional_branch, $relationships);
			$facts->fallback_conversion = Conversion_Preparation::decide($fallback->type, $facts->type, conversion_context::conditional_branch, $relationships);
			$facts->true_boundary = Value_Boundary_Preparation::decide_value($facts->type, $facts->true_conversion, $context, $selected);
			$facts->fallback_boundary = Value_Boundary_Preparation::decide_value($facts->type, $facts->fallback_conversion, $context, $fallback);
			$role = Ownership_Conversions::role($facts->type);
			if (($role !== null) && ($role->ownership_family() === ownership_kind::unique)) {
				$facts->value_origin = expression_value_origin::owned_selection_result;
			}
			return $facts;
		}
		finally { $context->statement_assignment = $statement_assignment; }
	}

	/** Symmetric conditional join; match and coalescing retain their separate admission rules. */
	private static function join(canonical_type_use $selected, canonical_type_use $fallback,
		preparation_context $context): canonical_type_use
	{
		$selected_handle = Type_Queries::nullable_handle_payload($selected);
		$fallback_handle = Type_Queries::nullable_handle_payload($fallback);
		if (($selected_handle !== null) || ($fallback_handle !== null)) {
			$joined = Selection_Result_Preparation::ownership_join($selected_handle ?? $selected,
				$fallback_handle ?? $fallback, $context);
			if (($selected_handle !== null) && $selected_handle->matches($joined)) { return $selected; }
			if (($fallback_handle !== null) && $fallback_handle->matches($joined)) { return $fallback; }
			throw new \RuntimeException('Nullable object selection requires an optional branch carrying the selected base type');
		}
		if ((Ownership_Conversions::role($selected) !== null) || (Ownership_Conversions::role($fallback) !== null)) {
			return Selection_Result_Preparation::ownership_join($selected, $fallback, $context);
		}
		$selected_role = Result_Conversions::role($selected);
		$fallback_role = Result_Conversions::role($fallback);
		if (($selected_role !== null) || ($fallback_role !== null)) {
			$result_wrapper = $selected_role !== null ? $selected : $fallback;
			$other = $selected_role !== null ? $fallback : $selected;
			return self::result_join($result_wrapper, $other, $context);
		}
		if (Boxed_Conversions::is_boxed($selected) || Boxed_Conversions::is_boxed($fallback)) {
			return Selection_Result_Preparation::boxed_join($selected, $fallback, $context);
		}
		if ((Nullable_Conversions::payload($selected) === null) && (Nullable_Conversions::payload($fallback) === null)) {
			return Selection_Result_Preparation::exact_join($selected, $fallback, $context, 'conditional');
		}
		$selected_domain = self::value_domain($selected);
		$fallback_domain = self::value_domain($fallback);
		Selection_Result_Preparation::nullable_value_join($selected_domain, $fallback_domain, $context);
		$result = $selected;
		if (Nullable_Conversions::payload($fallback) !== null) { $result = $fallback; }
		Capability_Preparation::require_copy($selected, $context);
		Capability_Preparation::require_copy($fallback, $context);
		Capability_Preparation::require_copy($result, $context);
		return $result;
	}

	/** A Result-led join preserves one exact wrapper identity, never mixes families. */
	private static function result_join(canonical_type_use $wrapper, canonical_type_use $other,
		preparation_context $context): canonical_type_use
	{
		$role /** result_semantics */ = Result_Conversions::role($wrapper);
		$payload = Result_Conversions::require_payload($wrapper, $role);
		if (!$other->matches($wrapper) && !$other->matches($payload)) {
			throw new \RuntimeException('S2S conditional Result branches require the same wrapper or exact payload');
		}
		Capability_Preparation::require_copy($wrapper, $context);
		Capability_Preparation::require_copy($other, $context);
		return $wrapper;
	}

	/** Bare null and broader carriers do not establish a conditional result domain. */
	private static function value_domain(canonical_type_use $type): canonical_type_use
	{
		$payload = Nullable_Conversions::payload($type);
		if ($payload !== null) {
			Nullable_Conversions::require_value_payload($payload);
			if ($type->by_value()) { throw new \RuntimeException('S2S nullable conditional branches do not support storage modifiers'); }
			return $payload;
		}
		Nullable_Conversions::require_value_payload($type);
		return $type;
	}
}
