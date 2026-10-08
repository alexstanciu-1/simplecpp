<?php

/* Resolve registered iteration signatures and lexical bindings, independently of C++ ranges. */
namespace scpp\compiler;

final class Iteration_Preparation
{
	/** Resolve the source before introducing loop-local keys or reusing value storage. */
	public static function prepare(foreach_node $syntax, preparation_context $context): prepared_iteration
	{
		$receiver = Expression_Preparation::prepare($syntax->iterable, $context)->type;
		if ($receiver->by_value()) {
			throw new \RuntimeException('S2S foreach with effective storage modifiers is deferred');
		}
		$type = Type_Queries::canonical($receiver);
		$contract = Model::$iteration_contracts->lookup($type->definition());
		if ($contract === null) {
			throw new \RuntimeException('S2S foreach requires a registered iterable type; wrapper unwrapping is deferred');
		}
		$arguments /** vector<canonical_type_use> */ = [];
		if ($type instanceof applied_template_type) {
			$arguments = object_cast($type, applied_template_type::class)->arguments();
		}
		$facts = new prepared_iteration();
		$facts->contract = $contract;
		$facts->key_type = Contract_Type_Substitution::resolve($contract->key(), $receiver, $arguments);
		$facts->value_type = Contract_Type_Substitution::resolve($contract->value(), $receiver, $arguments);
		if ($syntax->key !== null)
		{
			$key /** variable_reference_node */ = $syntax->key;
			if ($key->name === $syntax->value->name) {
				throw new \RuntimeException('S2S foreach key and value require distinct names');
			}
			$facts->key = self::bind($key, $facts->key_type, true, $context);
		}
		$facts->value = self::bind($syntax->value, $facts->value_type, false, $context);
		return $facts;
	}

	/** An implicit iteration value uses the same assignment conversion as an ordinary write. */
	private static function bind(variable_reference_node $syntax, canonical_type_use $source,
		bool $fresh, preparation_context $context): prepared_binding
	{
		$existing /** vector<prepared_storage> */ = [];
		if (!$fresh) {
			$existing = $context->locals->named($syntax->name);
		}
		$binding = new prepared_binding();
		$binding->type = $source;
		$binding->declaration = $syntax->occurrence();
		$binding->resolved_kind = binding_kind::declaration;
		if (q_count($existing) !== 0)
		{
			if (q_count($existing) !== 1) {
				throw new \RuntimeException('S2S foreach needs one value assignment target');
			}
			$binding->type = $existing[0]->storage_type();
			Reference_Preparation::require_writable(Reference_Preparation::storage_place($existing[0]));
			$binding->declaration = $existing[0]->declaration;
			$binding->resolved_kind = binding_kind::assignment;
		}
		$binding->conversion = Conversion_Preparation::decide($source, $binding->type, conversion_context::assignment,
			new Conversion_Relationships($context));
		Capability_Preparation::require_copy($source, $context);
		Capability_Preparation::require_copy($binding->type, $context);
		Type_Preparation::require_value_type($binding->type);
		if ($binding->resolved_kind === binding_kind::declaration) {
			$context->locals->add($syntax->name, $binding);
		}
		return $binding;
	}
}
