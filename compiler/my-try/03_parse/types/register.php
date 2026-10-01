<?php

/* Role: install the language and runtime definitions supported by the current compiler. */
namespace scpp\compiler;

final class Type_Definition_Registration
{
	private const RUNTIME_PROVIDER = 'simple_cpp.runtime';

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
		$providers->register_modifier(type_use_modifier_kind::by_value, self::RUNTIME_PROVIDER, 'value');
		return new registered_type_catalog($registry, $source, $providers);
	}

	/** Install each current scalar/string identity and its source exposure. */
	private static function install_language_types(Type_Registry $registry,
		Source_Type_Exposures $source): void
	{
		$void = $registry->define_no_value('void', type_definition_origin::language);
		self::install_concrete($registry, $source, $void);

		$boolean = $registry->define_boolean('bool', type_definition_origin::language);
		self::install_concrete($registry, $source, $boolean);

		foreach ([8, 16, 32, 64] as $bits)
		{
			$signed = $registry->define_integer('int' . $bits, type_definition_origin::language, $bits, true);
			self::install_concrete($registry, $source, $signed);
			$unsigned = $registry->define_integer('uint' . $bits, type_definition_origin::language, $bits, false);
			self::install_concrete($registry, $source, $unsigned);
		}

		$byte = $registry->define_integer('byte', type_definition_origin::language, 8, false);
		self::install_concrete($registry, $source, $byte);
		$integer = $registry->define_integer('int', type_definition_origin::language, 64, true);
		self::install_concrete($registry, $source, $integer);
		$floating = $registry->define_floating('float', type_definition_origin::language,
			floating_format::binary, 64, 53);
		self::install_concrete($registry, $source, $floating);
		$string = $registry->define_nominal('string', type_definition_origin::language,
			nominal_type_kind::class_type);
		self::install_concrete($registry, $source, $string);
	}

	/** Install runtime recipes and their known formation contracts without applying them. */
	private static function install_runtime_templates(Type_Registry $registry, Source_Type_Exposures $source,
		Runtime_Type_Providers $providers): void
	{
		$value_storable = [generic_contract::value_storable];
		$vector = $registry->define_template('vector', type_definition_origin::runtime,
			nominal_type_kind::class_type, [new template_type_parameter('Value', $value_storable)]);
		self::install_template($source, $providers, $vector, 'vector');

		$string_type = $registry->canonical(object_cast(
			$registry->definition($source->type('string')->definition_id()), concrete_type_definition_i::class));
		$hash = $registry->define_template('hash', type_definition_origin::runtime,
			nominal_type_kind::class_type, [
				new template_type_parameter('Value', $value_storable),
				new template_type_parameter('Key',
					[generic_contract::hashable, generic_contract::comparable],
					$registry->use($string_type->type_id())),
			]);
		self::install_template($source, $providers, $hash, 'hash');

		$nullable = $registry->define_template('nullable', type_definition_origin::runtime,
			nominal_type_kind::class_type, [new template_type_parameter('Value', $value_storable)]);
		self::install_template($source, $providers, $nullable, 'nullable');

		foreach (['shared', 'weak', 'unique'] as $name)
		{
			$definition = $registry->define_template($name, type_definition_origin::runtime,
				nominal_type_kind::class_type, [new template_type_parameter('Target', [])]);
			self::install_template($source, $providers, $definition, $name);
		}
	}

	/** Register language visibility and materialize one concrete built-in identity. */
	private static function install_concrete(Type_Registry $registry, Source_Type_Exposures $source,
		concrete_type_definition_i $definition): void
	{
		$source->expose_type($definition->name(), $definition);
		$registry->canonical($definition);
	}

	private static function install_template(Source_Type_Exposures $source, Runtime_Type_Providers $providers,
		template_type_definition $definition, string $runtime_family): void
	{
		$source->expose_type($definition->name(), $definition);
		$providers->register($definition, self::RUNTIME_PROVIDER, $runtime_family);
	}
}
