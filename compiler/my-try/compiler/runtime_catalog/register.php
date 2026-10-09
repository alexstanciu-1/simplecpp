<?php
namespace scpp\compiler;

/** Resolve a complete private callable catalog before publishing any scope entries. */
final class Runtime_Catalog_Registration
{
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
				if ($function->type_parameters !== []) {
					$generic = Runtime_Catalog_Patterns::contract($function, $types);
				}
				$parameters /** Storage<callable_parameter> */ = new Storage();
				$parameter_names /** hash<bool> */ = [];
				$overload_key = $function->name;
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
				if (isset($reference_names[$function->name])) {
					if ($has_reference || $reference_names[$function->name]) {
						throw new \RuntimeException('Runtime reference overloads are deferred: ' . $function->name);
					}
				}
				$reference_names[$function->name] = $has_reference;
				if (isset($observation_names[$function->name])) {
					if ($observation_names[$function->name] !== $has_observation) {
						throw new \RuntimeException('Runtime overloads cannot mix observation and consuming inputs');
					}
				}
				$observation_names[$function->name] = $has_observation;
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
			}
		}
		return $result;
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
				$language->register_function($catalog->function_definition($function->identity));
			}
		}
	}
}
