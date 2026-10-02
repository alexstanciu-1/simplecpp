<?php

/* Prepare executable bodies, local storage and statement boundaries. */
namespace scpp\compiler;

final class Body_Preparation
{
	/** Prepare all syntax, including unreachable statements; compose only reachable normal exits. */
	public static function prepare_statements(statement_body_node $body,
			preparation_context $context): statement_completion
	{
		$can_fall_through = true;
		$nodes /** Storage<statement_node> */ = $body->statements;
		foreach ($nodes as $node)
		{
			$completion = $node->prepare_completion($context);
			$can_fall_through = $can_fall_through && $completion->can_fall_through;
		}
		return new statement_completion($can_fall_through);
	}

	/** A block borrows the body context and restores its enclosing environment on every exit. */
	public static function prepare_block(statement_body_node $body, preparation_context $context): statement_completion
	{
		$enclosing = $context->locals;
		$context->locals = new local_environment($enclosing);
		try {
			return self::prepare_statements($body, $context);
		}
		finally {
			$context->locals = $enclosing;
		}
	}

	/** Validate every arm, while modelling the unmatched path of a chain without else. */
	public static function prepare_if(if_node $syntax, preparation_context $context): statement_completion
	{
		if ($syntax->arm_kind === if_arm_kind::else_arm)
		{
			if (($syntax->condition !== null) || ($syntax->next_arm !== null)) {
				throw new \LogicException('Else arm must have neither condition nor successor');
			}
			return $syntax->body->prepare_completion($context);
		}
		if ($syntax->condition === null) {
			throw new \LogicException('Conditional arm requires a condition');
		}
		$condition /** expression_node */ = $syntax->condition;
		$value = Expression_Preparation::prepare($condition, $context);
		$facts = new prepared_condition();
		$facts->conversion = Conversion_Preparation::decide($value->type, $context->boolean, conversion_context::condition);
		$syntax->set_preparation($facts);
		$taken = $syntax->body->prepare_completion($context);
		$unmatched = new statement_completion(true);
		if ($syntax->next_arm !== null)
		{
			$next /** if_node */ = $syntax->next_arm;
			if ($next->arm_kind === if_arm_kind::initial) {
				throw new \LogicException('Conditional successor must be elseif or else');
			}
			$unmatched = $next->prepare_completion($context);
		}
		return new statement_completion($taken->can_fall_through || $unmatched->can_fall_through);
	}

	/** Establish source-order local storage before publishing it to later statements. */
	public static function prepare_local_storage(collected_name $entry, ?type_node $type_syntax, ?expression_node $initializer, preparation_context $context): prepared_binding
	{
		$binding = new prepared_binding();
		$locals = $context->locals;
		$before_initializer /** vector<prepared_storage> */ = $locals->local_named($entry->name);
		if (($type_syntax !== null) && (q_count($before_initializer) !== 0)) {
			throw new \RuntimeException('S2S local ' . $entry->name . ' is already declared in this scope');
		}
		if ($type_syntax !== null) {
			$type /** type_node */ = $type_syntax;
			$binding->type = Type_Preparation::type($type, $context);
		}

		// Inferred assignment expressions evaluate inner writes before classifying the outer target.
		$value /** nullable<prepared_expression> */ = null;
		$previous_initializing /** nullable<string> */ = $locals->initializing_name;
		if ($type_syntax !== null) {
			$locals->initializing_name = $entry->name;
		}
		try {
			if ($initializer !== null) {
				$value_node /** expression_node */ = $initializer;
				$value = Expression_Preparation::prepare($value_node, $context);
			}
		}
		finally {
			$locals->initializing_name = $previous_initializing;
		}

		$previous /** vector<prepared_storage> */ = $type_syntax === null
			? $locals->named($entry->name)
			: $before_initializer;
		if (($type_syntax !== null) || (q_count($previous) === 0))
		{
			$binding->resolved_kind = binding_kind::declaration;
			$binding->declaration = $entry;
			if (($type_syntax === null) && ($value !== null)) {
				$binding->type = $value->type;
			}
		}
		else
		{
			if (q_count($previous) !== 1) {
				throw new \RuntimeException('S2S needs one local assignment target');
			}
			$binding->resolved_kind = binding_kind::assignment;
			$binding->declaration = $previous[0]->declaration;
			$binding->type = $previous[0]->type;
		}

		// The declaration is still unpublished while its initializer is prepared.
		if ($value !== null)
		{
			$has_expected_type = ($type_syntax !== null)
				|| ($binding->resolved_kind === binding_kind::assignment);
			if ($has_expected_type) {
				$binding->conversion = Conversion_Preparation::decide(
					$value->type, $binding->type, conversion_context::assignment);
			}
		}
		if ($value !== null) {
			Capability_Preparation::require_copy($binding->type, $context);
		}
		Type_Preparation::require_value_type($binding->type);
		if ($binding->resolved_kind === binding_kind::declaration) {
			$locals->add($entry->name, $binding);
		}
		return $binding;
	}

	public static function prepare_expression_statement(expression_statement_node $syntax, preparation_context $context): void
	{
		$expression = $syntax->expression;
		$context->statement_assignment = $expression instanceof assignment_expression_node;
		try {
			Expression_Preparation::prepare($expression, $context);
		}
		finally {
			$context->statement_assignment = false;
		}
	}

	/** Function returns use the signature; program-entry returns remain scalar exit values. */
	public static function prepare_return(return_node $syntax, preparation_context $context): prepared_return
	{
		$facts = new prepared_return();
		if ($syntax->expression === null)
		{
			if ($context->return_type !== null) {
				$type /** canonical_type_use */ = $context->return_type;
				if (Type_Preparation::canonical($type)->family() !== type_family::no_value) {
					throw new \RuntimeException('S2S non-void return requires a value');
				}
			}
			return $facts;
		}

		$expression /** expression_node */ = $syntax->expression;
		$value = Expression_Preparation::prepare($expression, $context);
		if ($context->return_type !== null) {
			$type /** canonical_type_use */ = $context->return_type;
			$facts->conversion = Conversion_Preparation::decide(
				$value->type, $type, conversion_context::return_value);
			if (Type_Preparation::canonical($type)->family() !== type_family::no_value) {
				Capability_Preparation::require_copy($type, $context);
			}
		}
		elseif (!Type_Preparation::entry_return_type($value->type)) {
			throw new \RuntimeException('S2S entry return requires an integer, float or bool value');
		}
		return $facts;
	}

	/** Seed the worker-owned body context with parameters before source-order bindings. */
	public static function prepare_body(function_node $syntax, preparation_context $context): void
	{
		$signature = $syntax->require_preparation();
		$context->return_type = $signature->return_type;
		$locals = $context->locals;
		$parameters /** Storage<prepared_parameter> */ = $signature->parameters;
		foreach ($parameters as $parameter) {
			$entry = object_cast(weakref_get($parameter->declaration), collected_name::class);
			$locals->add($entry->name, $parameter);
		}

		$completion = $syntax->body->prepare_completion($context);
		if ($completion->can_fall_through && (Type_Preparation::canonical($signature->return_type)->family() !== type_family::no_value)) {
			throw new \RuntimeException('S2S non-void function ' . $syntax->name . ' can reach the end without returning a value');
		}
	}
}
