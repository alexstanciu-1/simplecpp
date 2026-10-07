<?php

/* Handle empty-state boundaries; pointee access and ownership transfer are separate operations. */
namespace scpp\compiler;

final class Ownership_Conversions
{
	public static function role(canonical_type_use $type): ?ownership_semantics
	{
		if (Type_Queries::is_class_handle($type)) {
			return object_cast(Model::$type_catalog->definition('shared'), ownership_semantics::class);
		}
		$definition = Type_Queries::canonical($type)->definition();
		if (!($definition instanceof ownership_semantics)) {
			return null;
		}
		return object_cast($definition, ownership_semantics::class);
	}

	/** Ownership-template arguments name payloads; bare source classes carry the same role implicitly. */
	public static function payload(canonical_type_use $type): canonical_type_use
	{
		return Type_Queries::record_payload($type);
	}

	/** A weak observation cannot acquire a usable lifetime through a presence test. */
	public static function require_presence(canonical_type_use $type): void
	{
		$role /** ownership_semantics */ = self::role($type);
		if ($role->ownership_family() === ownership_kind::weak) {
			throw new \RuntimeException('S2S weak presence/truthiness requires an explicit lock');
		}
	}

	/** Null construction is not copying; other ownership-changing conversions remain gated. */
	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if ($source->matches($target)) {
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::identity);
		}
		if (Nullable_Conversions::is_null($source)) {
			if ((self::role($target) !== null) && ($context === conversion_context::assignment)) {
				return Conversion_Preparation::operation($source, $target, $context, conversion_operation::ownership_empty);
			}
		}
		throw new \RuntimeException('S2S ownership boundary requires matching handles or null storage initialization/assignment');
	}
}
