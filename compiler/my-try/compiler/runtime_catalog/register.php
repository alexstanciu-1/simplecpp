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
				$owner /** nullable<semantic_type_definition> */ = null;
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
		$names /** hash<bool> */ = [];
		$observation_names /** hash<bool> */ = [];
		foreach ($input->modules as $module)
		{
			$functions /** Storage<runtime_function_spec> */ = $module->functions;
			foreach ($functions as $function)
			{
				$generic /** nullable<runtime_generic_contract> */ = null;
				$member_owner /** nullable<semantic_type_definition> */ = null;
				if ($function->member !== null) {
					$membership /** runtime_callable_member_spec */ = $function->member;
					$member_owner = self::member_definition($membership->owner, $types);
				}
				if ((q_count($function->type_parameters) > 0) || ($member_owner instanceof abstract_template_type_definition)) {
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
				$has_observation = false;
				foreach ($function->parameters as $index => $parameter)
				{
					if (isset($parameter_names[$parameter->name])) {
						throw new \RuntimeException('Duplicate runtime parameter name');
					}
					$parameter_names[$parameter->name] = true;
					if ($parameter->mode === passing_mode::observation) { $has_observation = true; }
					if ($generic !== null) {
						$patterns /** Storage<runtime_pattern_parameter> */ = $generic->parameters;
						$overload_key .= ':' . Runtime_Catalog_Patterns::key($patterns[$index]->type);
						if ($parameter->variadic) { $overload_key .= ':pack'; }
						continue;
					}
					$resolved = self::parameter($parameter, $types);
					$parameters->append($resolved);
					$overload_key .= ':t' . $resolved->type->type_id();
					if ($parameter->variadic) { $overload_key .= ':pack'; }
				}
				// Return type/pass mode alone cannot distinguish runtime overloads.
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
				$result->function_definition($function->identity)->snapshot_borrow_safe = $function->snapshot_borrow_safe;
				foreach ($function->qualified_aliases as $qualifier) {
					if ($member_owner !== null) { throw new \RuntimeException('Runtime members cannot expose qualified aliases'); }
					self::validate_qualifier($qualifier);
					$result->register_qualified_alias($qualifier, $result->function_definition($function->identity));
				}
				if ($member_owner !== null) {
					$membership /** runtime_callable_member_spec */ = $function->member;
					$result->register_member($member_owner, $result->function_definition($function->identity), $membership->static_member);
				}
			}
		}
		return $result;
	}

	/** Bounded bare qualifiers, not namespace paths or contextual type keywords. */
	private static function validate_qualifier(string $name): void
	{
		if (($name === '') || ($name === 'self') || ($name === 'parent') || ($name === 'static')) {
			throw new \RuntimeException('Invalid runtime alias qualifier');
		}
		$length = string_byte_len($name);
		for ($index = 0; $index < $length; $index++) {
			$byte = string_byte_at($name, $index);
			$letter = (($byte >= 65) && ($byte <= 90)) || (($byte >= 97) && ($byte <= 122)) || ($byte === 95);
			$digit = ($index > 0) && ($byte >= 48) && ($byte <= 57);
			if (!$letter && !$digit) { throw new \RuntimeException('Invalid runtime alias qualifier'); }
		}
	}

	/** Members belong to definitions, not enumerated concrete template applications. */
	public static function member_definition(runtime_type_spec $spec, registered_type_catalog $types): semantic_type_definition
	{
		$arguments /** Storage<runtime_type_spec> */ = $spec->arguments;
		if (($spec->parameter !== '') || ($spec->callable !== null) || !$arguments->is_empty()) {
			throw new \RuntimeException('Runtime member owner must name a definition, not an application');
		}
		$definition = $types->definition($spec->name);
		if (($definition->origin() !== type_definition_origin::runtime)
			|| (!($definition instanceof abstract_nominal_type_definition)
				&& !($definition instanceof abstract_template_type_definition))) {
			throw new \RuntimeException('Runtime member requires a registered nominal or template owner');
		}
		return object_cast($definition, semantic_type_definition::class);
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
		if ($spec->callable !== null) {
			$callable /** runtime_callable_spec */ = $spec->callable;
			$signature = new callable_signature();
			$signature->return_type = self::type($callable->return_type, $types);
			$parameters /** Storage<callable_parameter> */ = $signature->parameters;
			$inputs /** vector<callable_parameter> */ = [];
			$names /** hash<bool> */ = [];
			foreach ($callable->parameters as $input) {
				if (isset($names[$input->name])) { throw new \RuntimeException('Duplicate runtime callable parameter name'); }
				$names[$input->name] = true;
				$parameter = self::parameter($input, $types);
				$parameters->append($parameter);
				$inputs[] = $parameter;
			}
			Callable_Contracts::validate($signature);
			$registry = $types->registry();
			$callable_definition = $registry->define_callable($signature->return_type, $inputs);
			return $registry->use($registry->canonical($callable_definition)->type_id());
		}
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

	/** One installed parameter path for functions and structural callable inputs. */
	public static function parameter(runtime_parameter_spec $spec, registered_type_catalog $types): callable_parameter
	{
		$registry = $types->registry();
		$result = new callable_parameter();
		$result->type = self::type($spec->type, $types);
		if ($registry->type($result->type->type_id()) instanceof no_value_type) { throw new \RuntimeException('Void runtime parameter'); }
		$result->mode = $spec->mode;
		$result->requires_copy = $spec->requires_copy;
		$result->access = $spec->access;
		if ($spec->variadic) {
			if (!$registry->has_capability($result->type, generic_contract::copyable_value)) {
				throw new \RuntimeException('Runtime variadic elements must be copyable');
			}
			$result->element_type = $result->type;
			$vector_definition = object_cast($types->definition('vector'), abstract_template_type_definition::class);
			$elements /** vector<canonical_type_use> */ = [$result->type];
			$result->type = $registry->use($registry->intern_application($vector_definition, $elements)->type_id());
		}
		if ($spec->default_value !== null) {
			$default_spec /** runtime_default_spec */ = $spec->default_value;
			$result->default_value = Runtime_Default_Registration::resolve($default_spec, $types);
			Runtime_Default_Registration::require_type($result->default_value, $result->type);
		}
		return $result;
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
