<?php

/* Role: construct canonical definitions from source declarations. */
namespace scpp\compiler;

final class Source_Types
{
	/** Preserve declaration provenance while constructing one shared type identity. */
	public static function definition(collected_struct $entry): nominal_type_definition
	{
		$type = Model::$type_catalog->define_source_structure($entry->name, $entry);
		return $type->nominal_definition();
	}
}
