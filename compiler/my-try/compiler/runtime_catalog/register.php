<?php
namespace scpp\compiler;

/** Resolve private callable and constant inventories before publishing scope entries. */
final class Runtime_Catalog_Registration
{
	/** Resolve values privately, rejecting collisions before publishing any module. */
	public static function resolve_constants(runtime_catalog_input $input, registered_type_catalog $types): Storage /** Storage<registered_constant> */
	{
		$result /** Storage<registered_constant> */ = new Storage();
		$names = new scope();
		$member_names /** hash<bool> */ = [];
		Language_Constants::install($names, $types);
		foreach ($input->modules as $module) {
			$constants /** Storage<runtime_constant_spec> */ = $module->constants;
			foreach ($constants as $constant) {
				if ($constant->type !== 'int') {
					throw new \RuntimeException('Runtime constants currently require int');
				}
				$owner /** ?semantic_type_definition */ = null;
				if ($constant->owner !== null)
				{
					$owner_spec /** runtime_type_spec */ = $constant->owner;
					$owner = self::member_definition($owner_spec, $types);
					$key = $owner->definition_id() . ':' . $constant->name;
					if (isset($member_names[$key])) {
						throw new \RuntimeException('Duplicate runtime member constant: ' . $constant->name);
					}
					$member_names[$key] = true;
				}
				elseif (q_count($names->constants_named($constant->name)) !== 0) {
					throw new \RuntimeException('Duplicate runtime constant: ' . $constant->name);
				}
				$value = new integer_constant_value();
				$value->type = $types->registry()->use($types->canonical('int')->type_id());
				$value->decimal = $constant->decimal;
				$target = new registered_constant($constant->name, $value, $owner);
				$result->append($target);
				if ($owner === null) { $names->register_constant($target); }
			}
		}
		return $result;
	}

	/** Resolve against caller-owned types; failure discards the private callable inventory. */
	public static function resolve(runtime_catalog_input $input, registered_type_catalog $types): Runtime_Callables
	{
		$result = new Runtime_Callables();
		$registry = $types->registry();
		$names /** hash<bool> */ = [];
		$reference_names /** hash<bool> */ = [];
		$observation_names /** hash<bool> */ = [];
		foreach ($input->modules as $module)
		{
			$functions /** Storage<runtime_function_spec> */ = $module->functions;
			foreach ($functions as $function)
			{
				$generic /** ?runtime_generic_contract */ = null;
				$member_owner /** ?semantic_type_definition */ = null;
				if ($function->member !== null) {
					$membership /** runtime_callable_member_spec */ = $function->member;
					$member_owner = self::member_definition($membership->owner, $types);
				}
				if (($function->type_parameters !== []) || ($member_owner instanceof abstract_template_type_definition)) {
					$generic = Runtime_Catalog_Patterns::contract($function, $types);
				}
				$parameters /** Storage<callable_parameter> */ = new Storage();
				$parameter_names /** hash<bool> */ = [];
				$lookup_key = $function->name;
				if ($function->member !== null)
				{
					$membership /** runtime_callable_member_spec */ = $function->member;
					if (string_byte_starts_with($function->name, '__')) {
						throw new \RuntimeException('Runtime magic member names are not callable metadata');
					}
					$definition_owner /** semantic_type_definition */ = $member_owner;
					$lookup_key = 'member:' . $definition_owner->definition_id() . ':' . $function->name;
				}
				$overload_key = $lookup_key;
				$has_reference = false;
				$has_observation = false;
				foreach ($function->parameters as $index => $parameter)
				{
					if (isset($parameter_names[$parameter->name])) {
						throw new \RuntimeException('Duplicate runtime parameter name');
					}
					$parameter_names[$parameter->name] = true;
					if ($parameter->mode === passing_mode::reference) { $has_reference = true; }
					if ($parameter->mode === passing_mode::observation) { $has_observation = true; }
					if ($generic !== null) {
						$patterns /** Storage<runtime_pattern_parameter> */ = $generic->parameters;
						$overload_key .= ':' . Runtime_Catalog_Patterns::key($patterns[$index]->type);
						continue;
					}
					$resolved = new callable_parameter();
					$resolved->type = self::type($parameter->type, $types);
					$parameter_type = $registry->type($resolved->type->type_id());
					if ($parameter_type instanceof no_value_type) {
						throw new \RuntimeException('Void runtime parameter');
					}
					$resolved->mode = $parameter->mode;
					$resolved->access = $parameter->access;
					$parameters->append($resolved);
					$overload_key .= ':t' . $resolved->type->type_id();
				}
				// Return type/pass mode alone cannot distinguish runtime overloads.
				if (isset($reference_names[$lookup_key])) {
					if ($has_reference || $reference_names[$lookup_key]) {
						throw new \RuntimeException('Runtime reference overloads are deferred: ' . $function->name);
					}
				}
				$reference_names[$lookup_key] = $has_reference;
				if (isset($observation_names[$lookup_key])) {
					if ($observation_names[$lookup_key] !== $has_observation) {
						throw new \RuntimeException('Runtime overloads cannot mix observation and consuming inputs');
					}
				}
				$observation_names[$lookup_key] = $has_observation;
				if (isset($names[$overload_key])) {
					throw new \RuntimeException('Duplicate runtime overload: ' . $function->name);
				}
				$names[$overload_key] = true;
				if ($generic !== null) {
					$result->register_generic($function->identity, $function->name, $generic);
				}
				else {
					$signature = new callable_signature();
					$signature->return_type = self::type($function->return_type, $types);
					$signature->parameters = $parameters;
					$result->register_function($function->identity, $function->name, $signature);
				}
				if ($member_owner !== null) {
					$membership /** runtime_callable_member_spec */ = $function->member;
					$result->register_member($member_owner, $result->function_definition($function->identity), $membership->static_member);
				}
			}
		}
		return $result;
	}

	/** Members belong to definitions, not enumerated concrete template applications. */
	public static function member_definition(runtime_type_spec $spec, registered_type_catalog $types): semantic_type_definition
	{
		if (($spec->parameter !== '') || !$spec->arguments->is_empty()) {
			throw new \RuntimeException('Runtime member owner must name a definition, not an application');
		}
		$definition = $types->definition($spec->name);
		if (($definition->origin() !== type_definition_origin::runtime)
			|| (!($definition instanceof abstract_nominal_type_definition)
				&& !($definition instanceof abstract_template_type_definition))) {
			throw new \RuntimeException('Runtime member requires a registered nominal or template owner');
		}
		return $definition;
	}

	/** Index retained identities; no metadata IO or duplicate value snapshots. */
	public static function members(Storage $constants /** Storage<registered_constant> */,
		runtime_catalog_input $input, registered_type_catalog $types): Runtime_Members
	{
		$result = new Runtime_Members();
		foreach ($constants as $constant) {
			if ($constant->owner() !== null) {
				$result->register_constant($constant);
			}
		}
		foreach ($input->modules as $module) {
			foreach ($module->properties as $spec)
			{
				$owner = self::member_definition($spec->owner, $types);
				$pattern = Runtime_Catalog_Patterns::type($spec->type, $types, Runtime_Catalog_Patterns::owner_positions($owner));
				$property = new runtime_property_definition($spec->identity, $spec->name, $owner, $pattern);
				if ($pattern instanceof runtime_concrete_pattern) {
					$concrete = object_cast($pattern, runtime_concrete_pattern::class);
					if (!$types->registry()->has_capability($concrete->type, generic_contract::value_storable)) {
						throw new \RuntimeException('Runtime property requires a storable type');
					}
				}
				$property->static_member = $spec->static_member;
				$property->access = $spec->access;
				$result->register_property($property);
			}
		}
		return $result;
	}

	/** Only ownerless constants participate in global language lookup. */
	public static function publish_constants(Storage $constants /** Storage<registered_constant> */, scope $language): void
	{
		foreach ($constants as $constant) {
			if ($constant->owner() === null) {
				$language->register_constant($constant);
			}
		}
	}

	/** Compose named/applied types through the existing registry formation rules. */
	public static function type(runtime_type_spec $spec, registered_type_catalog $types): canonical_type_use
	{
		if ($spec->parameter !== '') { throw new \RuntimeException('Type parameter requires a generic runtime contract'); }
		$definition = $types->definition($spec->name);
		$registry = $types->registry();
		$items /** Storage<runtime_type_spec> */ = $spec->arguments;
		if ($definition instanceof abstract_template_type_definition)
		{
			$arguments /** vector<canonical_type_use> */ = [];
			foreach ($items as $argument) {
				$arguments[] = self::type($argument, $types);
			}
			$application = $registry->intern_application(object_cast($definition, abstract_template_type_definition::class), $arguments);
			return $registry->use($application->type_id());
		}
		if (!$items->is_empty()) {
			throw new \RuntimeException('Type does not accept runtime catalog arguments: ' . $spec->name);
		}
		return $registry->use($types->canonical($spec->name)->type_id());
	}

	/** Index the installed identities without copying signatures or source declarations. */
	public static function publish(runtime_catalog_input $input, Runtime_Callables $catalog, scope $language): void
	{
		foreach ($input->modules as $module) {
			foreach ($module->functions as $function) {
				if ($function->member === null) {
					$language->register_function($catalog->function_definition($function->identity));
				}
			}
		}
	}
}
