<?php

/* Prepare typed expression facts and resolve call/member boundaries. */
namespace scpp\compiler;

final class Expression_Preparation
{
	/** Analysis owns family dispatch and attaches the typed result before returning it. */
	public static function prepare(expression_node $node, preparation_context $context,
		?canonical_type_use $expected_type = null): prepared_expression
	{
		if ($node instanceof data_construction_node) {
			$construction = object_cast($node, data_construction_node::class);
			$construction->set_preparation(Data_Construction_Preparation::prepare($construction, $context, $expected_type));
		}
		elseif ($node instanceof null_literal_node) {
			$null_literal = object_cast($node, null_literal_node::class);
			$null_literal->set_preparation(self::prepare_null());
		}
		elseif ($node instanceof integer_literal_node) {
			$integer_literal = object_cast($node, integer_literal_node::class);
			$integer_literal->set_preparation(self::prepare_integer($integer_literal, $context));
		}
		elseif ($node instanceof float_literal_node) {
			$float_literal = object_cast($node, float_literal_node::class);
			$float_literal->set_preparation(self::prepare_float($float_literal, $context));
		}
		elseif ($node instanceof boolean_literal_node) {
			$boolean_literal = object_cast($node, boolean_literal_node::class);
			$boolean_literal->set_preparation(self::prepare_boolean($boolean_literal->value, $context));
		}
		elseif ($node instanceof string_literal_node) {
			$string_literal = object_cast($node, string_literal_node::class);
			$string_literal->set_preparation(self::prepare_string($string_literal, $context));
		}
		elseif ($node instanceof interpolated_string_node) {
			$interpolated_string = object_cast($node, interpolated_string_node::class);
			$interpolated_string->set_preparation(self::prepare_interpolated_string($interpolated_string, $context));
		}
		elseif ($node instanceof variable_reference_node) {
			$variable = object_cast($node, variable_reference_node::class);
			$variable->set_preparation(self::prepare_reference($variable, $context));
		}
		elseif ($node instanceof constant_reference_node) {
			$constant = object_cast($node, constant_reference_node::class);
			$constant->set_preparation(self::prepare_constant_reference($constant, $context));
		}
		elseif ($node instanceof call_node) {
			$call = object_cast($node, call_node::class);
			$call->set_preparation(self::prepare_call($call, $context));
		}
		elseif ($node instanceof compound_assignment_expression_node) {
			$compound = object_cast($node, compound_assignment_expression_node::class);
			$compound->set_preparation(Mutation_Preparation::prepare_compound($compound, $context));
		}
		elseif ($node instanceof mutation_expression_node) {
			$mutation = object_cast($node, mutation_expression_node::class);
			$mutation->set_preparation(Mutation_Preparation::prepare($mutation, $context));
		}
		elseif ($node instanceof unary_expression_node) {
			$unary = object_cast($node, unary_expression_node::class);
			$unary->set_preparation(self::prepare_unary($unary, $context));
		}
		elseif ($node instanceof binary_expression_node) {
			$binary = object_cast($node, binary_expression_node::class);
			$binary->set_preparation(self::prepare_binary($binary, $context));
		}
		elseif ($node instanceof cast_expression_node) {
			$cast = object_cast($node, cast_expression_node::class);
			$cast->set_preparation(self::prepare_cast($cast, $context));
		}
		elseif ($node instanceof assignment_expression_node) {
			$assignment = object_cast($node, assignment_expression_node::class);
			$assignment->set_preparation(self::prepare_assignment($assignment, $context));
		}
		elseif ($node instanceof field_access_node) {
			$field_access = object_cast($node, field_access_node::class);
			$field_access->set_preparation(self::prepare_field_access($field_access, $context));
		}
		elseif ($node instanceof index_node) {
			$indexed = object_cast($node, index_node::class);
			$indexed->set_preparation(Operator_Preparation::prepare_index($indexed, $context));
		}
		elseif ($node instanceof match_expression_node) {
			$match_expression = object_cast($node, match_expression_node::class);
			$match_expression->set_preparation(Match_Preparation::prepare($match_expression, $context));
		}
		elseif ($node instanceof coalesce_expression_node) {
			$coalesce = object_cast($node, coalesce_expression_node::class);
			$coalesce->set_preparation(Coalesce_Expression_Preparation::prepare($coalesce, $context));
		}
		elseif ($node instanceof conditional_expression_node) {
			$conditional = object_cast($node, conditional_expression_node::class);
			$conditional->set_preparation(Conditional_Expression_Preparation::prepare($conditional, $context));
		}
		elseif ($node instanceof value_probe_node) {
			$probe = object_cast($node, value_probe_node::class);
			$probe->set_preparation(Value_Probe_Preparation::prepare($probe, $context));
		}
		elseif ($node instanceof take_expression_node) {
			$take_expression = object_cast($node, take_expression_node::class);
			$take_expression->set_preparation(Take_Preparation::prepare($take_expression, $context));
		}
		else {
			throw new \RuntimeException('Preparation is not supported for this node');
		}
		return $node->require_preparation();
	}

	/** A variable write enters local binding; supported member writes retain their exact field target. */
	public static function prepare_assignment(assignment_expression_node $node, preparation_context $context): prepared_assignment_expression
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = $statement_assignment && ($node->value instanceof assignment_expression_node);
		try
		{
			$target = $node->target;
			if ($target instanceof index_node) {
				return Indexing_Preparation::prepare_write($node, object_cast($target, index_node::class), $context);
			}
			$facts = new prepared_assignment();
			if ($target instanceof variable_reference_node) {
				$variable = object_cast($target, variable_reference_node::class);
				if (!$statement_assignment) {
					// Resolve before the RHS: nested writes cannot establish their own target.
					self::prepare_reference($variable, $context);
				}
				$facts->binding = Body_Preparation::prepare_local_storage($variable->occurrence(), null, $node->value, $context);
			}
			elseif ($statement_assignment && ($target instanceof field_access_node)) {
				$field = object_cast($target, field_access_node::class);
				$facts->binding = self::prepare_field_write($field, $node->value, $context);
			}
			else {
				throw new \RuntimeException('S2S assignment target is not supported in this context');
			}
			$facts->type = $facts->binding->type;
			if (!$statement_assignment) {
				Capability_Preparation::require_copy($facts->type, $context);
			}
			return $facts;
		}
		finally {
			$context->statement_assignment = $statement_assignment;
		}
	}

	/** Member assignment requires an addressable prepared field and never introduces a local. */
	private static function prepare_field_write(field_access_node $target, expression_node $initializer, preparation_context $context): prepared_binding
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try {
			self::prepare($target, $context);
		}
		finally {
			$context->statement_assignment = $statement_assignment;
		}
		$place = $target->require_field_access_preparation();
		if (!$place->addressable) {
			throw new \RuntimeException('S2S assignment requires stable storage');
		}
		$binding = new prepared_binding();
		$binding->type = $place->type;
		$binding->resolved_kind = binding_kind::assignment;
		$binding->declaration = $place->field->declaration;
		$value = self::prepare($initializer, $context, $binding->type);
		$binding->conversion = Conversion_Preparation::decide(
			$value->type, $binding->type, conversion_context::assignment);
		Capability_Preparation::require_copy($binding->type, $context);
		Type_Preparation::require_value_type($binding->type);
		return $binding;
	}

	public static function prepare_integer(integer_literal_node $node, preparation_context $context): prepared_integer_literal
	{
		$value = new prepared_integer_literal();
		$value->decimal = Integer_Literals::decimal($context->collection->token_snapshot()->text_at($node->start_token()));
		$value->type = $context->integer;
		return $value;
	}

	public static function prepare_null(): prepared_null_literal
	{
		$facts = new prepared_null_literal();
		$facts->type = Model::$type_catalog->null_type();
		return $facts;
	}

	/** Retain source spelling so the target, rather than host PHP, performs rounding. */
	public static function prepare_float(float_literal_node $node, preparation_context $context): prepared_float_literal
	{
		$value = new prepared_float_literal();
		$value->decimal = $context->collection->token_snapshot()->text_at($node->start_token());
		$value->type = $context->floating;
		return $value;
	}

	public static function prepare_boolean(bool $value, preparation_context $context): prepared_boolean_literal
	{
		$facts = new prepared_boolean_literal();
		$facts->value = $value;
		$facts->type = $context->boolean;
		return $facts;
	}

	public static function prepare_string(string_literal_node $node, preparation_context $context): prepared_string_literal
	{
		$facts = new prepared_string_literal();
		$text = $node->literal_spelling($context->collection->token_snapshot());
		$facts->value = String_Literals::decode($text);
		$facts->type = $context->string_type;
		return $facts;
	}

	/** Prepare every part in source order; inserted names use normal local/parameter lookup. */
	public static function prepare_interpolated_string(interpolated_string_node $node,
		preparation_context $context): prepared_interpolated_string
	{
		$parts /** Storage<interpolation_part_node> */ = $node->parts;
		foreach ($parts as $part) {
			self::prepare_part($part, $context);
		}
		$facts = new prepared_interpolated_string();
		$facts->type = $context->string_type;
		return $facts;
	}

	/** Interpolation parts are auxiliary syntax, not executable expressions. */
	private static function prepare_part(interpolation_part_node $part, preparation_context $context): void
	{
		if ($part instanceof interpolation_text_node) {
			$text_part = object_cast($part, interpolation_text_node::class);
			$text_part->set_preparation(self::prepare_interpolation_text($text_part, $context));
		}
		elseif ($part instanceof interpolation_value_node) {
			$value_part = object_cast($part, interpolation_value_node::class);
			$value_part->set_preparation(self::prepare_interpolation_value($value_part, $context));
		}
		else { throw new \RuntimeException('Preparation is not supported for this node'); }
	}

	/** Reuse the existing escape decoder on a complete literal-part boundary. */
	public static function prepare_interpolation_text(interpolation_text_node $node,
		preparation_context $context): prepared_string_literal
	{
		$token = $context->collection->token_snapshot()->text_at($node->start_token());
		$text = string_byte_slice($token, $node->byte_offset, $node->byte_length);
		$facts = new prepared_string_literal();
		$facts->value = String_Literals::decode('"' . $text . '"');
		$facts->type = $context->string_type;
		return $facts;
	}

	/** Scalar interpolation shares explicit string-cast policy, with its own boundary context. */
	public static function prepare_interpolation_value(interpolation_value_node $node,
		preparation_context $context): prepared_interpolation_value
	{
		$value = self::prepare($node->expression, $context);
		if (!Conversion_Preparation::is_scalar($value->type)) {
			throw new \RuntimeException('S2S interpolation requires a scalar value');
		}
		$facts = new prepared_interpolation_value();
		$facts->type = $context->string_type;
		$facts->conversion = Conversion_Preparation::decide(
			$value->type, $facts->type, conversion_context::interpolation);
		return $facts;
	}

	/** Prepare the operand first, then resolve and decide the source-written target request. */
	public static function prepare_cast(cast_expression_node $node,
		preparation_context $context): prepared_cast_expression
	{
		$operand = self::prepare($node->operand, $context);
		$target = Type_Preparation::type($node->target_type, $context);
		$facts = new prepared_cast_expression();
		$facts->conversion = Conversion_Preparation::decide(
			$operand->type, $target, conversion_context::explicit_cast);
		$facts->type = $facts->conversion->result_type;
		return $facts;
	}

	/** Prepare operands and retain the operation selected by the shared operator owner. */
	public static function prepare_unary(unary_expression_node $node, preparation_context $context): prepared_unary_expression
	{
		return Operator_Preparation::prepare_unary($node, $context);
	}

	public static function prepare_binary(binary_expression_node $node, preparation_context $context): prepared_binary_expression
	{
		return Operator_Preparation::prepare_binary($node, $context);
	}

	/** Resolve source-order locals without modifying the retained declaration inventory. */
	public static function prepare_reference(variable_reference_node $node, preparation_context $context): prepared_variable_reference
	{
		$entry = $node->occurrence();
		$locals = $context->locals;
		$targets /** vector<prepared_storage> */ = $locals->named($entry->name);
		if (q_count($targets) !== 1) {
			throw new \RuntimeException('S2S needs an established local declaration for ' . $entry->name);
		}

		$reference = new prepared_variable_reference();
		$reference->declaration = $targets[0]->declaration;
		$reference->type = $targets[0]->storage_type();
		$reference->addressable = true;
		return $reference;
	}

	/** Resolve constants lexically through their independent, exact-name namespace. */
	public static function prepare_constant_reference(constant_reference_node $node, preparation_context $context): prepared_constant_reference
	{
		$entry = $node->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$targets = Observed_Lookup::constants($lexical_scope, $entry->name, $context);
		if (q_count($targets) !== 1) {
			throw new \RuntimeException('S2S needs one resolved constant for ' . $entry->name);
		}

		$facts = new prepared_constant_reference();
		$facts->definition = $targets[0];
		$facts->type = $targets[0]->type;
		return $facts;
	}

	/** Resolve one named callable and establish reference/value argument boundaries. */
	public static function prepare_call(call_node $syntax, preparation_context $context): prepared_call
	{
		$templates /** Storage<named_type_node> */ = $syntax->template_arguments;
		if (!$templates->is_empty()) {
			throw new \RuntimeException('S2S template calls are deferred');
		}

		$entry = $syntax->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$targets = Observed_Lookup::functions($lexical_scope, $entry->name, $context);
		if (q_count($targets) !== 1) {
			throw new \RuntimeException('S2S needs one resolved function for ' . $entry->name);
		}

		return Call_Preparation::prepare($targets[0], $syntax->arguments, $context);
	}

	/** A member selection carries the exact field identity and inherits base addressability. */
	public static function prepare_field_access(field_access_node $syntax, preparation_context $context): prepared_field_access
	{
		$base = $syntax->base;
		$value = self::prepare($base, $context);
		$declaration = Type_Queries::source_record($value->type);
		if ($declaration === null) {
			throw new \RuntimeException('S2S member access requires a struct value');
		}

		$context->worker->require_record($value->type, $context);
		$record_entry /** collected_struct */ = $declaration;
		$record = $record_entry->syntax()->require_preparation();
		$fields /** Key_Storage_List<prepared_field> */ = $record->fields;
		$matches /** vector<prepared_field> */ = $fields->named($syntax->occurrence()->name);
		if (q_count($matches) !== 1) {
			throw new \RuntimeException('S2S needs one resolved struct field');
		}

		$facts = new prepared_field_access();
		$facts->field = $matches[0];
		$facts->type = $matches[0]->type;
		$facts->addressable = $value->addressable;

		return $facts;
	}
}
