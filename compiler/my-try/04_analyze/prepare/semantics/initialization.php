<?php

/* One interface selection/substitution path for every initialization provider. */
namespace scpp\compiler;

final class Initialization_Preparation
{
	/** Model-only query: does not prepare a literal or enable factory execution. */
	public static function binding(canonical_type_use $target, initialization_shape $shape,
		preparation_context $context): initialization_binding
	{
		$type = Type_Queries::canonical($target);
		if ($type->definition()->origin() === type_definition_origin::source) {
			throw new \RuntimeException('S2S source initialization interfaces are deferred');
		}
		$contracts = Model::$initialization_contracts;
		$arguments /** vector<canonical_type_use> */ = [];
		if ($type instanceof applied_template_type) {
			$application = object_cast($type, applied_template_type::class);
			$arguments = $application->arguments();
		}
		$selected = self::select($type->definition(), $arguments, $shape === initialization_shape::keyed, $contracts);
		if ($shape === initialization_shape::empty_brackets) {
			if ($selected === null) { $selected = self::select($type->definition(), $arguments, true, $contracts); }
		}
		if ($selected === null) { throw new \RuntimeException('S2S target does not support initialization shape'); }

		$value = Contract_Type_Substitution::resolve($selected->value(), $target, $arguments);
		$key_type /** nullable<canonical_type_use> */ = null;
		$key_reference = $selected->key();
		if ($key_reference !== null) { $key_type = Contract_Type_Substitution::resolve($key_reference, $target, $arguments); }
		$nested = Contract_Type_Substitution::resolve($selected->nested(), $target, $arguments);
		// Recheck source-derived capabilities even when the concrete binding is cached.
		$input_type = self::input_type($value, $key_type, $context);
		$existing = $contracts->existing($target, $selected->is_keyed());
		if ($existing !== null) { return $existing; }

		$signature = new callable_signature();
		$signature->return_type = $target;
		$parameter = new callable_parameter();
		$parameter->type = $input_type;
		$parameter->mode = passing_mode::value;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$parameters->append($parameter);
		$identity = $selected->factory_key() . ':' . Initialization_Contracts::binding_key($target, $selected->is_keyed());
		$factory = Model::$runtime_callables->register_static_factory($identity, $target, $signature);
		$binding = new initialization_binding($target, $value, $key_type, $nested, $factory);
		self::validate($binding, $signature, $context);
		$contracts->publish($binding);
		return $binding;
	}

	/** Validate an established signature; future source completion stays at the caller. */
	public static function validate(initialization_binding $binding, callable_signature $signature,
		preparation_context $context): void
	{
		$expected_input = self::input_type($binding->value(), $binding->key(), $context);
		if ($signature !== $binding->factory()->signature()) {
			throw new \InvalidArgumentException('Initialization signature must belong to its factory');
		}
		if (!$binding->target()->matches($signature->return_type)) {
			throw new \InvalidArgumentException('Initialization factory must return the exact target');
		}
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		if (q_count($parameters) !== 1) {
			throw new \InvalidArgumentException('Initialization factory requires one input sequence');
		}
		$parameter = $parameters[0];
		if (!$parameter->type->matches($expected_input) || ($parameter->mode !== passing_mode::value)
			|| ($parameter->default_value !== null) || ($parameter->element_type !== null)) {
			throw new \InvalidArgumentException('Initialization factory input contract mismatch');
		}
	}

	public static function input_type(canonical_type_use $value, ?canonical_type_use $key_type,
		preparation_context $context): canonical_type_use
	{
		$registry = Model::$type_catalog->registry();
		$contracts = Model::$initialization_contracts;
		$capabilities = new Capability_Preparation($context);
		$element = $value;
		if ($key_type !== null) {
			$entry_arguments /** vector<canonical_type_use> */ = [$value, $key_type];
			$entry = $registry->intern_application($contracts->entry(), $entry_arguments, $capabilities);
			$element = $registry->use($entry->type_id());
		}
		$sequence_arguments /** vector<canonical_type_use> */ = [$element];
		$sequence = $registry->intern_application($contracts->sequence(), $sequence_arguments, $capabilities);
		return $registry->use($sequence->type_id());
	}

	private static function select(type_definition_i $definition,
		array $arguments /** vector<canonical_type_use> */, bool $keyed,
		Initialization_Contracts $contracts): ?initialization_declaration
	{
		$selected /** nullable<initialization_declaration> */ = null;
		foreach ($contracts->candidates() as $candidate) {
			if (($candidate->definition() !== $definition) || ($candidate->is_keyed() !== $keyed)) { continue; }
			$matches = true;
			foreach ($candidate->constraints() as $position => $required) {
				if (!isset($arguments[$position])) { $matches = false; break; }
				if (!$arguments[$position]->matches($required)) { $matches = false; break; }
			}
			if (!$matches) { continue; }
			if ($selected !== null) { throw new \RuntimeException('S2S ambiguous initialization interface binding'); }
			$selected = $candidate;
		}
		return $selected;
	}
}
