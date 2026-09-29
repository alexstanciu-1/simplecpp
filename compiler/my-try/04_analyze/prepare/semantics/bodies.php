<?php

/* Prepare executable bodies, local storage and statement boundaries. */
namespace scpp\compiler;

final class Body_Preparation
{
	/** Bodies and blocks share source-order traversal with the caller's active context. */
	public static function prepare_statements(Storage $nodes /** Storage<statement_node> */, preparation_context $context): void
	{
		foreach ($nodes as $node) {
			$node->prepare($context);
		}
	}

	/** Establish source-order local storage before publishing it to later statements. */
	public static function prepare_local_storage(collected_name $entry, ?type_node $type_syntax, ?expression_node $initializer, preparation_context $context): prepared_binding
	{
		$binding = new prepared_binding();
		$locals /** Key_Storage_List<prepared_storage> */ = $context->locals;
		$previous /** vector<prepared_storage> */ = $locals->named($entry->name);
		if (($type_syntax !== null) || (q_count($previous) === 0))
		{
			$binding->resolved_kind = binding_kind::declaration;
			$binding->declaration = $entry;
			if ($type_syntax !== null) {
				$type /** type_node */ = $type_syntax;
				$binding->type = Type_Preparation::type($type, $context);
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

		// An initializer cannot see the declaration currently being introduced.
		if ($initializer !== null)
		{
			$value_node /** expression_node */ = $initializer;
			$value = Expression_Preparation::prepare($value_node, $context);
			if (($binding->resolved_kind === binding_kind::declaration) && ($type_syntax === null)) {
				$binding->type = $value->type;
			}
			Type_Preparation::require_assignable($binding->type, $value->type);
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
		Expression_Preparation::prepare($expression, $context);
	}

	/** Function returns use the signature; program-entry returns remain scalar exit values. */
	public static function prepare_return(return_node $syntax, preparation_context $context): void
	{
		if ($syntax->expression === null)
		{
			if ($context->return_type !== null) {
				$type /** type_definition */ = $context->return_type;
				if ($type->kind !== type_kind::void_type) {
					throw new \RuntimeException('S2S non-void return requires a value');
				}
			}
			return;
		}

		$expression /** expression_node */ = $syntax->expression;
		$value = Expression_Preparation::prepare($expression, $context);
		if ($context->return_type !== null) {
			$type /** type_definition */ = $context->return_type;
			Type_Preparation::require_assignable($type, $value->type);
		}
		elseif (($value->type->kind === type_kind::record) || ($value->type->kind === type_kind::void_type)) {
			throw new \RuntimeException('S2S entry return requires a scalar value');
		}
	}

	/** Each body receives fresh locals; parameters enter before source-order bindings. */
	public static function prepare_body(function_node $syntax, preparation_context $outer): void
	{
		$context = new preparation_context();
		$context->collection = $outer->collection;
		$context->worker = $outer->worker;
		$context->owner = $outer->owner;
		$context->integer = $outer->integer;
		$context->boolean = $outer->boolean;
		$context->floating = $outer->floating;
		$context->locals = new Key_Storage_List /** Key_Storage_List<prepared_storage> */();

		$signature = $syntax->require_preparation();
		$context->return_type = $signature->return_type;
		$locals /** Key_Storage_List<prepared_storage> */ = $context->locals;
		$parameters /** Storage<prepared_parameter> */ = $signature->parameters;
		foreach ($parameters as $parameter) {
			$entry = object_cast(weakref_get($parameter->declaration), collected_name::class);
			$locals->add($entry->name, $parameter);
		}

		Body_Preparation::prepare_statements($syntax->body->statements, $context);
	}
}
