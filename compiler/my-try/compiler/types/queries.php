<?php

/* Read established canonical identities and source associations without semantic processing. */
namespace scpp\compiler;

final class Type_Queries
{
	/** Nominal enum identity stays distinct from its prepared integer storage. */
	public static function is_enum(canonical_type_use $type): bool
	{
		return self::canonical($type)->definition() instanceof enum_type_definition;
	}

	public static function canonical(canonical_type_use $type): canonical_type_i
	{
		return Model::$type_catalog->registry()->type($type->type_id());
	}

	/** Resolve only established source provenance; runtime/language definitions have no source owner. */
	public static function source_declaration(canonical_type_use $type): ?collected_type_definition
	{
		$canonical = self::canonical($type);
		$definition = $canonical->definition();
		if ($definition->origin() !== type_definition_origin::source) {
			return null;
		}
		return Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
	}

	/** A source type need not have fields or record layout. */
	public static function source_record(canonical_type_use $type): ?collected_struct
	{
		$declaration = self::source_declaration($type);
		if ($declaration instanceof collected_struct) {
			return object_cast($declaration, collected_struct::class);
		}
		return null;
	}
}
