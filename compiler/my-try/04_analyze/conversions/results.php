<?php

/* Result value boundaries; canonical roles and identities, never source spelling. */
namespace scpp\compiler;

final class Result_Conversions
{
	/** Query the definition's language role without depending on its published name. */
	public static function role(canonical_type_use $type): ?result_semantics
	{
		$definition = Type_Queries::canonical($type)->definition();
		if (!($definition instanceof result_semantics)) {
			return null;
		}
		return object_cast($definition, result_semantics::class);
	}

	public static function is_boolean_sentinel(canonical_type_use $type): bool
	{
		return $type->matches(Model::$type_catalog->boolean_sentinel_type(false))
			|| $type->matches(Model::$type_catalog->boolean_sentinel_type(true));
	}

	/** One payload domain for all Result consumers; no implicit nested conversion. */
	public static function require_payload(canonical_type_use $type, result_semantics $role): canonical_type_use
	{
		$application = object_cast(Type_Queries::canonical($type), applied_template_type::class);
		$payload = $role->payload_type($application);
		if ($type->by_value() || $payload->by_value()
			|| (!Conversion_Preparation::is_scalar($payload) && !Boxed_Conversions::is_boxed($payload)
				&& !(Type_Queries::canonical($payload)->definition() instanceof callable_type_definition))) {
			throw new \RuntimeException('S2S Result operations require an unmodified scalar, mixed, dynamic or explicit callable payload');
		}
		return $payload;
	}

	/** Runtime observation support is independent of payload storage/extraction support. */
	public static function require_observation_payload(canonical_type_use $type, result_semantics $role): canonical_type_use
	{
		$payload = self::require_payload($type, $role);
		if (Type_Queries::canonical($payload)->definition() instanceof callable_type_definition) {
			throw new \RuntimeException('S2S callable Result conditions and probes are deferred; extract with take first');
		}
		return $payload;
	}

	/** Storage admission does not silently extend conditional/coalescing joins. */
	public static function require_selection_payload(canonical_type_use $type, result_semantics $role): canonical_type_use
	{
		$payload = self::require_payload($type, $role);
		if (Type_Queries::canonical($payload)->definition() instanceof callable_type_definition) {
			throw new \RuntimeException('S2S callable Result selection joins are deferred');
		}
		return $payload;
	}

	/** Route required extraction separately from wrapper construction and same-type copies. */
	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		$target_role = self::role($target);
		if ($target_role === null) {
			return self::extract($source, $target, $context);
		}
		$role /** result_semantics */ = $target_role;
		$payload = self::require_payload($target, $role);
		if ($source->matches($target)) {
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::identity);
		}
		if ($source->matches($payload)) {
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::result_wrap);
		}
		if ($role->result_family() !== result_kind::structured_error)
		{
			if (Nullable_Conversions::is_null($source)
				|| $source->matches(Model::$type_catalog->boolean_sentinel_type(false))) {
				return Conversion_Preparation::operation($source, $target, $context, conversion_operation::result_false);
			}
			if ($role->result_family() === result_kind::boolean_sentinels)
			{
				if ($source->matches(Model::$type_catalog->boolean_sentinel_type(true))) {
					return Conversion_Preparation::operation($source, $target, $context, conversion_operation::result_true);
				}
			}
		}
		throw new \RuntimeException('S2S Result construction requires an exact payload, same wrapper type or supported explicit sentinel');
	}

	/** Required and selected coalescing values copy a checked payload; casts stay separate. */
	private static function extract(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if (($context !== conversion_context::assignment) && ($context !== conversion_context::argument)
			&& ($context !== conversion_context::return_value) && ($context !== conversion_context::coalesce_value)) {
			throw new \RuntimeException('S2S Result extraction supports only required assignment, argument and return boundaries or selected coalesce values');
		}
		$source_role = self::role($source);
		if ($source_role === null) {
			throw new \LogicException('Result extraction requires a Result source');
		}
		$payload = self::require_payload($source, $source_role);
		if (!$payload->matches($target)) {
			throw new \RuntimeException('S2S Result extraction requires the exact payload type');
		}
		return Conversion_Preparation::operation($source, $target, $context, conversion_operation::result_extract);
	}
}
