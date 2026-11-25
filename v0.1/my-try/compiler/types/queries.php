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
		$storage = record_storage_kind::inline_value;
		if (take_nullable($storage, Model::$type_catalog->registry()->runtime_storage($definition))) {
			if ($storage === record_storage_kind::shared) { return true; }
		}
		if (self::is_source_class($type)) { return true; }
		return self::source_declaration($type) instanceof collected_interface;
	}

	/** Optional default handles preserve nullable identity while sharing handle absence storage. */
	public static function nullable_handle_payload(canonical_type_use $type): ?canonical_type_use
	{
		if ($type->by_value()) { return null; }
		$canonical = self::canonical($type);
		$definition = $canonical->definition();
		if (!($definition instanceof nullable_semantics)) { return null; }
		$payload = object_cast($definition, nullable_semantics::class)->payload_type(
			object_cast($canonical, applied_template_type::class));
		if (self::is_nominal_handle($payload)) { return $payload; }
		return null;
	}

	/** Member selection interprets optional handle storage without weakening type identity. */
	public static function receiver_payload(canonical_type_use $type): canonical_type_use
	{
		$payload = self::nullable_handle_payload($type);
		return self::record_payload($payload ?? $type);
	}

	public static function receiver_storage(canonical_type_use $type): record_storage_kind
	{
		$payload = self::nullable_handle_payload($type);
		return self::record_storage($payload ?? $type);
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

/** Read settled capabilities without scheduling work or retaining a preparation context. */
final class Capability_Facts implements type_capability_query
{
	/** Shared nominal handles compare/hash by stable identity, independently of payload layout. */
	public static function nominal_handle_capability(generic_contract $capability): bool
	{
		return ($capability === generic_contract::copyable_value)
			|| ($capability === generic_contract::value_storable)
			|| ($capability === generic_contract::hashable)
			|| ($capability === generic_contract::comparable);
	}

	/** Recurse through registered argument rules while reading completed source facts. */
	public function has_capability(canonical_type_use $type_use, generic_contract $capability): bool
	{
		$definition = Type_Queries::canonical($type_use)->definition();
		if (Type_Queries::is_nominal_handle($type_use) && ($definition->origin() === type_definition_origin::source)) {
			return self::nominal_handle_capability($capability);
		}
		$declaration = Type_Queries::source_declaration($type_use);
		if ($declaration === null) {
			return Model::$type_catalog->registry()->has_registered_capability($type_use, $capability, $this);
		}
		$work = $declaration->preparation_work_owner();
		if ($work === null) {
			throw new \LogicException('Capability facts require completed declaration preparation');
		}
		if (!Preparation_Status::complete($work)) {
			throw new \LogicException('Capability facts require completed declaration preparation');
		}
		if ($declaration instanceof collected_record) {
			return object_cast($declaration, collected_record::class)->syntax()->require_preparation()->has_capability($capability);
		}
		if ($declaration instanceof collected_enum) {
			return object_cast($declaration, collected_enum::class)->enum_syntax()->require_preparation()->has_capability($capability);
		}
		throw new \LogicException('Capability facts are unavailable for this source declaration');
	}
}
