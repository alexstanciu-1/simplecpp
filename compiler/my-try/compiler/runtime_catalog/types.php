<?php
namespace scpp\compiler;

/** Startup worker: ordinary recipes -> existing registry definitions, before signatures. */
final class Runtime_Type_Registration
{
	private Keyed_Storage $recipes /** Keyed_Storage<runtime_type_definition_spec> */;
	private array $owners /** hash<string> */ = [];
	private array $active /** hash<bool> */ = [];
	private array $complete /** hash<bool> */ = [];

	public function __construct(private registered_type_catalog $types)
	{
		$this->recipes = new Keyed_Storage();
	}

	/** Caller owns this private catalog; failure never publishes it into Model. */
	public static function install(runtime_catalog_input $input, registered_type_catalog $types): void
	{
		$worker = new Runtime_Type_Registration($types);
		$recipes /** Keyed_Storage<runtime_type_definition_spec> */ = $worker->recipes;
		$identities /** hash<bool> */ = [];
		foreach ($input->modules as $module)
		{
			foreach ($module->types as $recipe)
			{
				self::identifier($recipe->name);
				if (isset($identities[$recipe->identity])) {
					throw new \RuntimeException('Duplicate runtime type identity: ' . $recipe->identity);
				}
				$identities[$recipe->identity] = true;
				if (isset($recipes[$recipe->name]) || ($types->source()->type_or_null($recipe->name) !== null)
					|| ($types->source()->modifier_or_null($recipe->name) !== null)) {
					throw new \RuntimeException('Duplicate runtime type name: ' . $recipe->name);
				}
				$recipes->add($recipe->name, $recipe);
				$worker->owners[$recipe->name] = $module->name;
			}
		}
		foreach ($recipes as $recipe) {
			$worker->define($recipe->name);
		}
	}

	/** Defaults may refer forward to other recipes; active dependencies diagnose cycles. */
	private function define(string $name): void
	{
		$recipes /** Keyed_Storage<runtime_type_definition_spec> */ = $this->recipes;
		if (!isset($recipes[$name]) || isset($this->complete[$name])) {
			return;
		}
		if (isset($this->active[$name])) {
			throw new \RuntimeException('Runtime type default cycle: ' . $name);
		}
		$this->active[$name] = true;
		$recipe = $recipes[$name];
		$parameters /** vector<template_type_parameter> */ = [];
		$names /** hash<bool> */ = [];
		foreach ($recipe->parameters as $parameter)
		{
			self::identifier($parameter->name);
			if (isset($names[$parameter->name])) {
				throw new \RuntimeException('Duplicate runtime type parameter: ' . $parameter->name);
			}
			$names[$parameter->name] = true;
			$default_type /** ?canonical_type_use */ = null;
			if ($parameter->default_type !== null)
			{
				$default_spec /** runtime_type_spec */ = $parameter->default_type;
				$this->require_default($default_spec);
				$resolved_default = Runtime_Catalog_Registration::type($default_spec, $this->types);
				foreach ($parameter->formation_contracts as $contract) {
					if (!$this->types->registry()->has_capability($resolved_default, $contract)) {
						throw new \RuntimeException('Runtime type default violates ' . Generic_Contract_Name::text($contract));
					}
				}
				$default_type = $resolved_default;
			}
			$parameters[] = new template_type_parameter($parameter->name, $parameter->formation_contracts,
				$default_type, $parameter->capability_dependencies);
		}
		$definition = $this->register_definition($recipe, $parameters);
		$this->types->registry()->register_runtime_storage($definition, $recipe->storage);
		$this->types->source()->expose_type($name, $definition);
		$this->types->providers()->register($definition, 'simple_cpp.runtime.' . $this->owners[$name], $recipe->identity);
		unset($this->active[$name]);
		$this->complete[$name] = true;
	}

	/** Nominals intern once; templates retain recipes until an application is demanded. */
	private function register_definition(runtime_type_definition_spec $recipe,
		array $parameters /** vector<template_type_parameter> */): type_definition_i
	{
		if (q_count($parameters) === 0) {
			$nominal = $this->types->registry()->define_nominal($recipe->name, type_definition_origin::runtime,
				$recipe->kind, $recipe->capabilities);
			$this->types->registry()->canonical($nominal);
			return $nominal;
		}
		return $this->types->registry()->define_template($recipe->name, type_definition_origin::runtime,
			$recipe->kind, $parameters, $recipe->capabilities);
	}

	/** Ensure the named dependencies exist before the ordinary type resolver runs. */
	private function require_default(runtime_type_spec $spec): void
	{
		if ($spec->callable !== null) {
			$callable /** runtime_callable_spec */ = $spec->callable;
			$this->require_default($callable->return_type);
			foreach ($callable->parameters as $input) {
				if ($input->variadic) { $this->define('vector'); }
				$this->require_default($input->type);
			}
			return;
		}
		if ($spec->parameter !== '') {
			throw new \RuntimeException('Runtime type defaults require closed types');
		}
		$this->define($spec->name);
		foreach ($spec->arguments as $argument) {
			$this->require_default($argument);
		}
	}

	/** Bounded source names; qualified/aliased declarations are a separate extension. */
	private static function identifier(string $name): void
	{
		if ($name === '') {
			throw new \RuntimeException('Empty runtime type identifier');
		}
		for ($index = 0; $index < string_byte_len($name); $index++)
		{
			$byte = string_byte_at($name, $index);
			if ((($byte >= 65) && ($byte <= 90)) || (($byte >= 97) && ($byte <= 122)) || ($byte === 95)) {
				continue;
			}
			if (($index > 0) && ($byte >= 48) && ($byte <= 57)) {
				continue;
			}
			throw new \RuntimeException('Invalid runtime type identifier: ' . $name);
		}
	}
}
