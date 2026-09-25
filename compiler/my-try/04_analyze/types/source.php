<?php

/* Role: construct canonical definitions from source declarations. */
namespace scpp\compiler;

final class Source_Types
{
	/** Preserve declaration provenance while constructing one shared type identity. */
	public static function definition(collected_name $entry): type_definition
	{
		$type = new type_definition();
		$type->name = $entry->name;
		$type->kind = type_kind::record;
		$type->origin = type_origin::source;
		$type->declaration = $entry;
		return $type;
	}
}
