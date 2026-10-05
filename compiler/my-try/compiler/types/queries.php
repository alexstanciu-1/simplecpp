<?php

/* Read established canonical identities and source associations without semantic processing. */
namespace scpp\compiler;

final class Type_Queries
{
	public static function canonical(canonical_type_use $type): canonical_type_i
	{
		return Model::$type_catalog->registry()->type($type->type_id());
	}

	public static function source_record(canonical_type_use $type): ?collected_struct
	{
		$canonical = self::canonical($type);
		$definition = $canonical->definition();
		if ($definition->origin() !== type_definition_origin::source) {
			return null;
		}
		return Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
	}
}
