<?php

/* Role: construct canonical definitions from source declarations. */
namespace scpp\compiler;

final class Source_Types
{
	/** Preserve declaration provenance while constructing one shared type identity. */
	public static function definition(collected_type_definition $entry): abstract_nominal_type_definition
	{
		if ($entry instanceof collected_struct) {
			$type = Model::$type_catalog->define_source_structure($entry->qualified_name(),
				object_cast($entry, collected_struct::class));
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
