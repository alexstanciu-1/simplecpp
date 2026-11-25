<?php

/* Role: construct canonical definitions from source declarations. */
namespace scpp\compiler;

final class Source_Types
{
	/** Read the identity already published by collection, without allocating another type. */
	public static function definition(collected_type_definition $entry): abstract_nominal_type_definition
	{
		$declaring_scope /** scope */ = weakref_get($entry->scope);
		foreach ($declaring_scope->types_named($entry->name) as $definition) {
			if ($definition->origin() !== type_definition_origin::source) { continue; }
			$declaration = Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
			if ($declaration === $entry) { return object_cast($definition, abstract_nominal_type_definition::class); }
		}
		throw new \LogicException('Source type definition has not been published');
	}

	/** Collection-only creation; the collector publishes under its existing shared lock. */
	public static function create_definition(collected_type_definition $entry): abstract_nominal_type_definition
	{
		if ($entry instanceof collected_trait) {
			$type = Model::$type_catalog->define_source_trait($entry->qualified_name(),
				object_cast($entry, collected_trait::class));
			return $type->nominal_definition();
		}
		if ($entry instanceof collected_interface) {
			$type = Model::$type_catalog->define_source_interface($entry->qualified_name(),
				object_cast($entry, collected_interface::class));
			return $type->nominal_definition();
		}
		if ($entry instanceof collected_record) {
			$type = Model::$type_catalog->define_source_record($entry->qualified_name(),
				object_cast($entry, collected_record::class));
			return $type->nominal_definition();
		}
		if ($entry instanceof collected_enum) {
			$enum_type = Model::$type_catalog->define_source_enum($entry->qualified_name(),
				object_cast($entry, collected_enum::class));
			return $enum_type->nominal_definition();
		}
		throw new \LogicException('Unsupported source type declaration');
	}
}
