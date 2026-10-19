<?php

/* Prepare executable bodies, local storage and statement boundaries. */
namespace scpp\compiler;

final class Body_Preparation
{
	/** Analysis owns statement dispatch, fact attachment and completion composition. */
	public static function prepare_statement(statement_node $node, preparation_context $context): statement_completion
	{
		if ($node instanceof throw_node) {
			return Exception_Preparation::prepare_throw(object_cast($node, throw_node::class), $context);
		}
		if ($node instanceof try_node) {
			return Exception_Preparation::prepare_try(object_cast($node, try_node::class), $context);
		}
		if ($node instanceof echo_node) {
			$output = object_cast($node, echo_node::class);
			$operands /** Storage<expression_node> */ = $output->operands;
			foreach ($operands as $operand) {
				$value = Expression_Preparation::prepare($operand, $context);
				self::require_output_type($value->type);
			}
			return new statement_completion(true);
		}
		if ($node instanceof cleanup_node) {
			$removal = object_cast($node, cleanup_node::class);
			$removal->set_preparation(Cleanup_Preparation::prepare($removal, $context));
			return new statement_completion(true);
		}
		if ($node instanceof function_body_node) {
			return self::prepare_statements(object_cast($node, function_body_node::class), $context, 0);
		}
		if ($node instanceof statement_body_node) {
			return self::prepare_block(object_cast($node, statement_body_node::class), $context);
		}
		if ($node instanceof if_node) {
			return self::prepare_if(object_cast($node, if_node::class), $context);
		}
		if ($node instanceof loop_node) {
			return Loop_Preparation::prepare(object_cast($node, loop_node::class), $context);
		}
		if ($node instanceof switch_node) {
			return Switch_Preparation::prepare(object_cast($node, switch_node::class), $context);
		}
		if ($node instanceof control_transfer_node) {
			$transfer = object_cast($node, control_transfer_node::class);
			$transfer->set_preparation(Control_Transfer_Preparation::prepare($transfer, $context));
			return Completion_Preparation::transfer($transfer->transfer_kind());
		}
		if ($node instanceof return_node) {
			$return_statement = object_cast($node, return_node::class);
			$return_statement->set_preparation(self::prepare_return($return_statement, $context));
			return Completion_Preparation::returned();
		}
		if ($node instanceof expression_statement_node) {
			self::prepare_expression_statement(object_cast($node, expression_statement_node::class), $context);
			return new statement_completion(true);
		}
		if ($node instanceof variable_declaration_node) {
			$variable = object_cast($node, variable_declaration_node::class);
			if ($variable->binds_reference) {
				$variable->set_preparation(Reference_Preparation::bind(
					$variable->occurrence(), $variable->type_syntax, $variable->initializer, $context));
				return new statement_completion(true);
			}
			$variable->set_preparation(self::prepare_local_storage(
				$variable->occurrence(), $variable->type_syntax, $variable->initializer, $context));
			return new statement_completion(true);
		}
		throw new \RuntimeException('Preparation is not supported for this node');
	}

	/** Runtime output formats values directly; this is not a required string extraction. */
	private static function require_output_type(canonical_type_use $type): void
	{
		if (Conversion_Preparation::is_scalar($type) || Nullable_Conversions::is_null($type)
			|| Boxed_Conversions::is_boxed($type)) { return; }
		$payload = Nullable_Conversions::payload($type);
		if ($payload !== null) { self::require_output_type($payload); return; }
		$role = Result_Conversions::role($type);
		if ($role !== null) {
			self::require_output_type(Result_Conversions::require_payload($type, $role));
			return;
		}
		throw new \RuntimeException('Echo requires a scalar, null, mixed, dynamic or supported wrapper payload');
	}

	/** Prepare all syntax, including unreachable statements; compose only reachable normal exits. */
	public static function prepare_statements(statement_body_node $body,
			preparation_context $context, int $start): statement_completion
	{
		$result = new statement_completion(true);
		$nodes /** Storage<statement_node> */ = $body->statements;
		foreach ($nodes as $index => $node)
		{
			if ($index < $start) { continue; }
			$completion = self::prepare_statement($node, $context);
			$result = Completion_Preparation::sequence($result, $completion);
		}
		return $result;
	}

	/** A block borrows the body context and restores its enclosing environment on every exit. */
	public static function prepare_block(statement_body_node $body, preparation_context $context): statement_completion
	{
		$enclosing = $context->locals;
		$context->locals = new local_environment($enclosing);
		try {
			return self::prepare_statements($body, $context, 0);
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
			return self::prepare_block($syntax->body, $context);
		}
		if ($syntax->condition === null) {
			throw new \LogicException('Conditional arm requires a condition');
		}
		$condition /** expression_node */ = $syntax->condition;
		$facts = self::prepare_condition($condition, $context);
		$syntax->set_preparation($facts);
		$taken = self::prepare_block($syntax->body, $context);
		$unmatched = new statement_completion(true);
		if ($syntax->next_arm !== null)
		{
			$next /** if_node */ = $syntax->next_arm;
			if ($next->arm_kind === if_arm_kind::initial) {
				throw new \LogicException('Conditional successor must be elseif or else');
			}
			$unmatched = self::prepare_if($next, $context);
		}
		return Completion_Preparation::alternatives($taken, $unmatched);
	}

	/** All structured tests share condition truthiness, never explicit bool conversion. */
	public static function prepare_condition(expression_node $condition, preparation_context $context): prepared_condition
	{
		$value = Expression_Preparation::prepare($condition, $context);
		$facts = new prepared_condition();
		$facts->truthiness = Condition_Truthiness::decide($value->type);
		return $facts;
	}

	/** Establish source-order local storage before publishing it to later statements. */
	public static function prepare_local_storage(collected_name $entry, ?type_node $type_syntax, ?expression_node $initializer, preparation_context $context): prepared_binding
	{
		if ($entry->name === 'this') { throw new \RuntimeException('$this cannot be declared or rebound'); }
		$binding = new prepared_binding();
		$locals = $context->locals;
		$before_initializer /** vector<prepared_source_storage> */ = $locals->local_named($entry->name);
		if (($type_syntax !== null) && (q_count($before_initializer) !== 0)) {
			throw new \RuntimeException('S2S local ' . $entry->name . ' is already declared in this scope');
		}
		$expected_type /** nullable<canonical_type_use> */ = null;
		if ($type_syntax !== null) {
			$type /** type_node */ = $type_syntax;
			$binding->type = Type_Preparation::local_type($type, $initializer, $context);
			$expected_type = $binding->type;
		}
		else {
			// Only an already established destination supplies context to a literal.
			$existing /** vector<prepared_source_storage> */ = $locals->named($entry->name);
			if (q_count($existing) === 1) { $expected_type = $existing[0]->storage_type(); }
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
				$consumer = expression_consumer::local_storage;
				if ($type_syntax !== null) {
					$consumer = expression_consumer::typed_local_initialization;
				}
				$value = Expression_Preparation::prepare_for_consumer($value_node, $context, $expected_type, $consumer);
			}
		}
		finally {
			$locals->initializing_name = $previous_initializing;
		}

		$previous /** vector<prepared_source_storage> */ = $type_syntax === null
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
			Reference_Preparation::require_writable(Reference_Preparation::storage_place($previous[0]));
			$binding->declaration = $previous[0]->declaration;
			$binding->type = $previous[0]->storage_type();
			if ($value !== null) {
				if (($value->value_origin === expression_value_origin::explicit_transfer)
					|| ($value->value_origin === expression_value_origin::owned_call_result)) {
					Move_Preparation::require_whole_place(Reference_Preparation::storage_place($previous[0]));
				}
			}
		}

		// The declaration is still unpublished while its initializer is prepared.
		if ($value !== null)
		{
			$has_expected_type = ($type_syntax !== null)
				|| ($binding->resolved_kind === binding_kind::assignment);
			if ($has_expected_type) {
				$binding->conversion = Conversion_Preparation::decide(
					$value->type, $binding->type, conversion_context::assignment, new Conversion_Relationships($context));
			}
		}
		Type_Preparation::require_value_type($binding->type);
		Type_Lifecycle_Preparation::require_storage($binding->type, $context);
		if ($value === null)
		{
			if (Type_Queries::record_storage($binding->type) === record_storage_kind::inline_value) {
				if (Type_Queries::source_record(Type_Queries::record_payload($binding->type)) !== null) {
					if (!Object_Construction_Preparation::supports_default($binding->type, $context)) {
						throw new \RuntimeException('Inline record storage requires an explicit initializer');
					}
				}
			}
			if (Type_Queries::canonical($binding->type)->definition() instanceof enum_type_definition) {
				throw new \RuntimeException('S2S enum storage requires an explicit initializer');
			}
			if (Result_Conversions::role($binding->type) !== null) {
				throw new \RuntimeException('S2S Result construction requires an explicit initializer');
			}
		}
		if ($value !== null) {
			$binding->boundary = Value_Boundary_Preparation::decide_value($binding->type, $binding->conversion, $context, $value);
		}
		if ($binding->resolved_kind === binding_kind::declaration) {
			$locals->add($entry->name, $binding);
		}
		if ($value !== null) {
			if (Type_Queries::canonical($value->type)->definition() instanceof closure_type_definition) {
				$destination = $binding->resolved_kind === binding_kind::declaration
					? $locals->lifetime_of($binding)
					: Storage_Lifetimes::place(Reference_Preparation::storage_place($previous[0]), $context);
				Storage_Lifetimes::require_destination(Capture_Preparation::borrowed_lifetimes($value->type, $context), $destination, $context);
			}
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
		Exception_Preparation::require_return($context);
		if ($context->reference_return_parameter !== null) {
			return Reference_Preparation::prepare_return($syntax, $context);
		}
		$facts = new prepared_return();
		if ($syntax->expression === null)
		{
			if ($context->return_type !== null) {
				$type /** canonical_type_use */ = $context->return_type;
				if (Type_Queries::canonical($type)->family() !== type_family::no_value) {
					throw new \RuntimeException('S2S non-void return requires a value');
				}
			}
			return $facts;
		}

		$expression /** expression_node */ = $syntax->expression;
		$value = Expression_Preparation::prepare_for_consumer($expression, $context, $context->return_type,
			expression_consumer::consuming_return);
		if ($context->return_type !== null) {
			$type /** canonical_type_use */ = $context->return_type;
			if (Type_Queries::canonical($type)->family() === type_family::no_value) {
				if (Type_Queries::canonical($value->type)->family() !== type_family::no_value) {
					throw new \RuntimeException('Void return forwarding requires a void expression; values are not discarded');
				}
				// A return forwards completion, not a storable value. Ordinary value conversion still rejects void.
				$facts->conversion = Conversion_Preparation::operation($value->type, $type,
					conversion_context::return_value, conversion_operation::identity);
				return $facts;
			}
			$facts->conversion = Conversion_Preparation::decide(
				$value->type, $type, conversion_context::return_value, new Conversion_Relationships($context));
			$facts->boundary = Value_Boundary_Preparation::decide_value($type, $facts->conversion, $context, $value);
		}
		elseif (!Type_Preparation::entry_return_type($value->type)) {
			throw new \RuntimeException('S2S entry return requires an integer, float or bool value');
		}
		return $facts;
	}

	/** Seed the worker-owned body context with parameters before source-order bindings. */
	public static function prepare_body(callable_declaration_node $syntax, preparation_context $context): void
	{
		$signature = $syntax->require_preparation();
		if ($syntax instanceof method_node) {
			$method = object_cast($syntax->occurrence(), collected_method::class);
			$context->member_owner = $method->declaring_type();
			$context->receiver = $signature->receiver;
			if ($context->receiver !== null) { $context->locals->add('this', $context->receiver); }
		}
		self::bind_parameters($signature, $context);
		$start = Constructor_Preparation::prepare_base($syntax, $context);
		self::prepare_callable_statements($syntax->require_body(), $signature, $context, $start, $syntax->name);
	}

	public static function bind_parameters(prepared_source_callable $signature, preparation_context $context): void
	{
		$context->return_type = $signature->signature->return_type;
		Reference_Preparation::begin_body($signature, $context);
		$locals = $context->locals;
		$parameters /** Storage<prepared_parameter> */ = $signature->parameters;
		foreach ($parameters as $parameter) {
			$entry = object_cast(weakref_get($parameter->declaration), collected_name::class);
			$locals->add($entry->name, $parameter);
		}

	}

	public static function prepare_callable_statements(function_body_node $body, prepared_source_callable $signature, preparation_context $context, int $start, string $name): void
	{
		$completion = self::prepare_statements($body, $context, $start);
		if ($completion->can_fall_through && (Type_Queries::canonical($signature->signature->return_type)->family() !== type_family::no_value)) {
			throw new \RuntimeException('S2S non-void function ' . $name . ' can reach the end without returning a value');
		}
	}
}
