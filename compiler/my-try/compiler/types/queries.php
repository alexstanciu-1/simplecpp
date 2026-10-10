<?php

/* Read established canonical identities and source associations without semantic processing. */
namespace scpp\compiler;

final class Type_Queries
{
	/** Common classification for construction, member access and backend representation. */
	public static function record_storage(canonical_type_use $type): record_storage_kind
	{
		$definition = self::canonical($type)->definition();
		if ($definition instanceof ownership_semantics)
		{
			$kind = object_cast($definition, ownership_semantics::class)->ownership_family();
			if ($kind === ownership_kind::weak) {
				return record_storage_kind::weak;
			}
			return $kind === ownership_kind::unique ? record_storage_kind::unique : record_storage_kind::shared;
		}
		return self::is_nominal_handle($type) ? record_storage_kind::shared : record_storage_kind::inline_value;
	}

	/** Unwrap ownership or an inline modifier without changing the nominal payload identity. */
	public static function record_payload(canonical_type_use $type): canonical_type_use
	{
		$definition = self::canonical($type)->definition();
		if ($definition instanceof ownership_semantics) {
			return object_cast($definition, ownership_semantics::class)->target_type(object_cast(self::canonical($type), applied_template_type::class));
		}
		return Model::$type_catalog->registry()->use($type->type_id());
	}

	/** Default class storage is shared; the canonical identity still names the payload. */
	public static function is_source_class(canonical_type_use $type): bool
	{
		$definition = self::canonical($type)->definition();
		if ($definition->origin() !== type_definition_origin::source) {
			return false;
		}
		if (!($definition instanceof abstract_nominal_type_definition)) {
			return false;
		}
		return object_cast($definition, abstract_nominal_type_definition::class)->nominal_kind() === nominal_type_kind::class_type;
	}

	/** Classes and interfaces carry default shared storage, not an ownership-template layer. */
	public static function is_nominal_handle(canonical_type_use $type): bool
	{
		if ($type->by_value()) { return false; }
		$definition = self::canonical($type)->definition();
		if (Model::$type_catalog->registry()->runtime_storage($definition) === record_storage_kind::shared) {
			return true;
		}
		if (self::is_source_class($type)) { return true; }
		return self::source_declaration($type) instanceof collected_interface;
	}

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
		if ($definition instanceof closure_type_definition) { return null; }
		if ($definition->origin() !== type_definition_origin::source) {
			return null;
		}
		return Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
	}

	/** A source type need not have fields or record layout. */
	public static function source_record(canonical_type_use $type): ?collected_record
	{
		$declaration = self::source_declaration($type);
		if ($declaration instanceof collected_record) {
			return object_cast($declaration, collected_record::class);
		}
		return null;
	}
}
