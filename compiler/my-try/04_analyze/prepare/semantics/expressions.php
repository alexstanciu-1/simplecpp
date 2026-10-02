<?php

/* Prepare typed expression facts and resolve call/member boundaries. */
namespace scpp\compiler;

final class Expression_Preparation
{
	/** Prepare through the specialized node and return the facts it attached. */
	public static function prepare(expression_node $node, preparation_context $context): prepared_expression
	{
		$node->prepare($context);
		return $node->require_preparation();
	}

	/** A variable write enters local binding; supported member writes retain their exact field target. */
	public static function prepare_assignment(assignment_expression_node $node, preparation_context $context): prepared_assignment
	{
		$target = $node->target;
		$facts = new prepared_assignment();
		if ($target instanceof variable_reference_node) {
			$variable = object_cast($target, variable_reference_node::class);
			$facts->binding = Body_Preparation::prepare_local_storage($variable->occurrence(), null, $node->value, $context);
		}
		elseif ($target instanceof field_access_node) {
			$field = object_cast($target, field_access_node::class);
			$facts->binding = self::prepare_field_write($field, $node->value, $context);
		}
		else {
			throw new \RuntimeException('S2S assignment target is not supported yet');
		}
		$facts->type = $facts->binding->type;
		return $facts;
	}

	/** Member assignment requires an addressable prepared field and never introduces a local. */
	private static function prepare_field_write(field_access_node $target, expression_node $initializer, preparation_context $context): prepared_binding
	{
		$target->prepare($context);
		$place = $target->require_field_access_preparation();
		if (!$place->addressable) {
			throw new \RuntimeException('S2S assignment requires stable storage');
		}
		$binding = new prepared_binding();
		$binding->type = $place->type;
		$binding->resolved_kind = binding_kind::assignment;
		$binding->declaration = $place->field->declaration;
		$value = Expression_Preparation::prepare($initializer, $context);
		$binding->conversion = Conversion_Preparation::decide(
			$value->type, $binding->type, conversion_context::assignment);
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
		$text = $context->collection->token_snapshot()->text_at($node->start_token());
		$facts->value = String_Literals::decode($text);
		$facts->type = $context->string_type;
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
		$locals /** Key_Storage_List<prepared_storage> */ = $context->locals;
		$targets /** vector<prepared_storage> */ = $locals->named($entry->name);
		if (q_count($targets) !== 1) {
			throw new \RuntimeException('S2S needs an established local declaration for ' . $entry->name);
		}

		$reference = new prepared_variable_reference();
		$reference->declaration = $targets[0]->declaration;
		$reference->type = $targets[0]->type;
		$reference->addressable = true;
		return $reference;
	}

	/** Resolve constants lexically through their independent, exact-name namespace. */
	public static function prepare_constant_reference(constant_reference_node $node, preparation_context $context): prepared_constant_reference
	{
		$entry = $node->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$targets = Scope_Lookup::constants($lexical_scope, $entry->name, $context);
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
		$targets = Scope_Lookup::functions($lexical_scope, $entry->name, $context);
		if (q_count($targets) !== 1) {
			throw new \RuntimeException('S2S needs one resolved function for ' . $entry->name);
		}

		$target = $targets[0];
		$context->worker->require_declaration($context->owner, $target);
		$facts = new prepared_call();
		$facts->declaration = $target;
		$facts->signature = $target->syntax()->require_preparation();
		$facts->type = $facts->signature->return_type;

		$parameters /** Storage<prepared_parameter> */ = $facts->signature->parameters;
		$arguments /** Storage<expression_node> */ = $syntax->arguments;
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		if (q_count($parameters) !== q_count($arguments)) {
			throw new \RuntimeException('S2S call argument count does not match its signature');
		}

		// Prepare arguments in source order; references must preserve the selected storage.
		foreach ($arguments as $index => $argument)
		{
			$value = Expression_Preparation::prepare($argument, $context);
			$parameter = $parameters[$index];
			$prepared_argument = new prepared_call_argument();
			if ($parameter->mode === passing_mode::reference) {
				if ((!$value->addressable) || !Type_Preparation::same_storage_type($value->type, $parameter->type)) {
					throw new \RuntimeException('S2S reference arguments require stable storage of the exact parameter type');
				}
			}
			else {
				$prepared_argument->conversion = Conversion_Preparation::decide(
					$value->type, $parameter->type, conversion_context::argument);
			}
			$prepared_arguments->append($prepared_argument);
		}

		return $facts;
	}

	/** A member selection carries the exact field identity and inherits base addressability. */
	public static function prepare_field_access(field_access_node $syntax, preparation_context $context): prepared_field_access
	{
		$base = $syntax->base;
		$value = Expression_Preparation::prepare($base, $context);
		$declaration = Type_Preparation::source_record($value->type);
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
