<?php

/* Bind stable places and verify bounded, caller-owned reference returns. */
namespace scpp\compiler;

final class Reference_Preparation
{
	/** Addressability permits borrowing; mutation additionally requires writable access. */
	public static function require_writable(prepared_place $place): void
	{
		if ($place->access() === storage_access::read_only) {
			throw new \RuntimeException('S2S mutation or mutable borrowing through read-only storage is not allowed');
		}
	}

	/** Alias names keep their own declaration identity but never acquire a new storage root. */
	public static function storage_place(prepared_storage $storage): prepared_place
	{
		if ($storage instanceof prepared_binding) {
			$binding = object_cast($storage, prepared_binding::class);
			if ($binding->alias_target !== null) {
				return $binding->alias_target;
			}
		}
		return new prepared_place($storage);
	}

	/** Source-order publication and no rebinding keep the owner alive for the alias's lexical scope. */
	public static function bind(collected_name $entry, ?type_node $type_syntax,
		?expression_node $initializer, preparation_context $context): prepared_binding
	{
		if (!$context->alias_declarations_allowed) {
			throw new \RuntimeException('S2S loop-header reference binding is deferred');
		}
		if ($initializer === null) {
			throw new \RuntimeException('S2S reference binding requires an initializer');
		}
		$locals = $context->locals;
		$existing /** vector<prepared_storage> */ = $type_syntax === null
			? $locals->named($entry->name) : $locals->local_named($entry->name);
		if (q_count($existing) !== 0) {
			throw new \RuntimeException('S2S reference binding requires a new local; rebinding is not supported');
		}
		$binding = new prepared_binding();
		$binding->declaration = $entry;
		$binding->resolved_kind = binding_kind::declaration;
		$expected /** nullable<canonical_type_use> */ = null;
		if ($type_syntax !== null) {
			$declared_type /** type_node */ = $type_syntax;
			$expected = Type_Preparation::type($declared_type, $context);
		}
		$previous_name = $locals->initializing_name;
		$previous_assignment = $context->statement_assignment;
		$locals->initializing_name = $entry->name;
		$context->statement_assignment = false;
		$prepared_value /** nullable<prepared_expression> */ = null;
		try {
			$source /** expression_node */ = $initializer;
			$prepared_value = Expression_Preparation::prepare($source, $context);
		}
		finally {
			$locals->initializing_name = $previous_name;
			$context->statement_assignment = $previous_assignment;
		}
		$value /** prepared_expression */ = $prepared_value;
		if ($value->place === null) {
			throw new \RuntimeException('S2S reference binding requires stable storage');
		}
		if (!$value->place->borrowable()) {
			throw new \RuntimeException('S2S reference binding cannot borrow heap-backed properties');
		}
		$binding->type = $expected ?? $value->type;
		if (!Type_Preparation::same_storage_type($binding->type, $value->type)) {
			throw new \RuntimeException('S2S reference binding requires the exact storage type');
		}
		Type_Preparation::require_value_type($binding->type);
		$binding->alias_target = $value->place;
		$binding->boundary = value_boundary_operation::bind_reference;
		$locals->add($entry->name, $binding);
		return $binding;
	}

	/** The unique reference parameter makes the return obligation declaration-local. */
	public static function prepare_signature(callable_declaration_node $syntax, callable_signature $signature): void
	{
		if (!$syntax->signature_syntax->returns_reference) {
			return;
		}
		$selected = -1;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		foreach ($parameters as $index => $parameter)
		{
			if ($parameter->mode === passing_mode::reference) {
				if ($selected !== -1) {
					throw new \RuntimeException('S2S reference return requires exactly one reference parameter');
				}
				$selected = $index;
			}
		}
		if ($selected === -1) {
			throw new \RuntimeException('S2S reference return requires exactly one reference parameter');
		}
		$signature->reference_return = new reference_return_contract($selected);
	}

	/** Every return shares one declaration-owned origin; ordinary completion checks fallthrough. */
	public static function begin_body(callable_declaration_node $syntax, preparation_context $context): void
	{
		$facts = $syntax->require_preparation();
		if ($facts->signature->reference_return === null) {
			return;
		}
		$contract /** reference_return_contract */ = $facts->signature->reference_return;
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		$context->reference_return_parameter = $parameters[$contract->parameter_index()];
	}

	/** Returning a local alias is safe only when its entire selected place is the borrowed parameter. */
	public static function prepare_return(return_node $syntax, preparation_context $context): prepared_return
	{
		if ($syntax->expression === null) {
			throw new \RuntimeException('S2S reference return requires the whole reference parameter');
		}
		$expression /** expression_node */ = $syntax->expression;
		$value = Expression_Preparation::prepare($expression, $context);
		if ($value->place === null) {
			throw new \RuntimeException('S2S reference return requires the whole reference parameter');
		}
		$place /** prepared_place */ = $value->place;
		if (($place->base() !== null) || ($place->storage() !== $context->reference_return_parameter)) {
			throw new \RuntimeException('S2S reference return cannot escape local, value-parameter or interior storage');
		}
		$facts = new prepared_return();
		$facts->reference_target = $place;
		$facts->boundary = value_boundary_operation::bind_reference;
		return $facts;
	}
}
