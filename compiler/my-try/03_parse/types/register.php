<?php

/* Role: install the language and runtime definitions supported by the current compiler. */
namespace scpp\compiler;

final class Type_Definition_Registration
{
	private static function runtime_provider(): string
	{
		return 'simple_cpp.runtime';
	}

	/** Install concrete definitions eagerly, but no applied template type. */
	public static function install(Type_Registry $registry): registered_type_catalog
	{
		if (($registry->definition_count() !== 0) || ($registry->type_count() !== 0)) {
			throw new \InvalidArgumentException('Built-in types require an empty registry');
		}
		$source = new Source_Type_Exposures();
		$providers = new Runtime_Type_Providers();
		self::install_language_types($registry, $source);
		self::install_runtime_templates($registry, $source, $providers);
		$source->expose_modifier('value', type_use_modifier_kind::by_value);
		$providers->register_modifier(type_use_modifier_kind::by_value, self::runtime_provider(), 'value');
		return new registered_type_catalog($registry, $source, $providers);
	}

	/** Install each current scalar/string identity and its source exposure. */
	private static function install_language_types(Type_Registry $registry,
		Source_Type_Exposures $source): void
	{
		$no_value_definition = $registry->define_no_value('void', type_definition_origin::language);
		self::install_concrete($registry, $source, $no_value_definition);

		$scalar_capabilities /** vector<generic_contract> */ = [generic_contract::copyable_value, generic_contract::value_storable,
			generic_contract::hashable, generic_contract::comparable];
		$boolean = $registry->define_boolean('bool', type_definition_origin::language, $scalar_capabilities);
		self::install_concrete($registry, $source, $boolean);

		$integer_widths /** vector<int> */ = [8, 16, 32, 64];
		foreach ($integer_widths as $bits)
		{
			$signed_definition = $registry->define_integer('int' . $bits, type_definition_origin::language, $bits, true,
				$scalar_capabilities);
			self::install_concrete($registry, $source, $signed_definition);
			$unsigned_definition = $registry->define_integer('uint' . $bits, type_definition_origin::language, $bits, false,
				$scalar_capabilities);
			self::install_concrete($registry, $source, $unsigned_definition);
		}

		$source->expose_type('byte', $registry->definition($source->type('uint8')->definition_id()));
		$integer = $registry->define_integer('int', type_definition_origin::language, 64, true,
			$scalar_capabilities);
		self::install_concrete($registry, $source, $integer);
		$floating = $registry->define_floating('float', type_definition_origin::language,
			floating_format::binary, 64, 53, $scalar_capabilities);
		self::install_concrete($registry, $source, $floating);
		$string = $registry->define_nominal('string', type_definition_origin::language,
			nominal_type_kind::class_type, $scalar_capabilities);
		self::install_concrete($registry, $source, $string);
	}

	/** Install runtime recipes and their known formation contracts without applying them. */
	private static function install_runtime_templates(Type_Registry $registry, Source_Type_Exposures $source,
		Runtime_Type_Providers $providers): void
	{
		$value_storable /** vector<generic_contract> */ = [generic_contract::value_storable];
		$copyable_storage /** vector<generic_contract> */ = [generic_contract::copyable_value,
			generic_contract::value_storable];
		$vector_parameters /** vector<template_type_parameter> */ = [
			new template_type_parameter('Value', $value_storable),
		];
		$vector = $registry->define_template('vector', type_definition_origin::runtime,
			nominal_type_kind::class_type, $vector_parameters,
			$copyable_storage);
		self::install_template($source, $providers, $vector, 'vector');

		$string_type = $registry->canonical(object_cast(
			$registry->definition($source->type('string')->definition_id()), concrete_type_definition_i::class));
		$hash_key_contracts /** vector<generic_contract> */ = [generic_contract::hashable,
			generic_contract::comparable];
		$hash_parameters /** vector<template_type_parameter> */ = [
				new template_type_parameter('Value', $value_storable),
				new template_type_parameter('Key', $hash_key_contracts,
					$registry->use($string_type->type_id())),
			];
		$hash = $registry->define_template('hash', type_definition_origin::runtime,
			nominal_type_kind::class_type, $hash_parameters, $copyable_storage);
		self::install_template($source, $providers, $hash, 'hash');

		$nullable_parameters /** vector<template_type_parameter> */ = [
			new template_type_parameter('Value', $value_storable),
		];
		$nullable = $registry->define_template('nullable', type_definition_origin::runtime,
			nominal_type_kind::class_type, $nullable_parameters,
			$copyable_storage);
		self::install_template($source, $providers, $nullable, 'nullable');

		$ownership_names /** vector<string> */ = ['shared', 'weak', 'unique'];
		foreach ($ownership_names as $name)
		{
			$result_capabilities = $name === 'unique' ? $value_storable : $copyable_storage;
			$target_contracts /** vector<generic_contract> */ = [];
			$target_parameters /** vector<template_type_parameter> */ = [
				new template_type_parameter('Target', $target_contracts),
			];
			$definition = $registry->define_template($name, type_definition_origin::runtime,
				nominal_type_kind::class_type, $target_parameters,
				$result_capabilities);
			self::install_template($source, $providers, $definition, $name);
		}
	}

	/** Register language visibility and materialize one concrete built-in identity. */
	private static function install_concrete(Type_Registry $registry, Source_Type_Exposures $source,
		concrete_type_definition_i $definition): void
	{
		$semantic_definition = object_cast($definition, type_definition_i::class);
		$source->expose_type($semantic_definition->name(), $semantic_definition);
		$registry->canonical($definition);
	}

	private static function install_template(Source_Type_Exposures $source, Runtime_Type_Providers $providers,
		template_type_definition $definition, string $runtime_family): void
	{
		$source->expose_type($definition->name(), $definition);
		$providers->register($definition, self::runtime_provider(), $runtime_family);
	}
}
