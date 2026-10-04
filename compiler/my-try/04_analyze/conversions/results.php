<?php

/* Result value boundaries; canonical roles and identities, never source spelling. */
namespace scpp\compiler;

final class Result_Conversions
{
	/** Query the definition's language role without depending on its published name. */
	public static function role(canonical_type_use $type): ?result_semantics
	{
		$definition = Type_Preparation::canonical($type)->definition();
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

	/** Formation alone does not admit broader payload operations in this first pass. */
	public static function require_scalar_payload(canonical_type_use $type, result_semantics $role): canonical_type_use
	{
		$application = object_cast(Type_Preparation::canonical($type), applied_template_type::class);
		$payload = $role->payload_type($application);
		if ($type->by_value() || $payload->by_value() || !Conversion_Preparation::is_scalar($payload)) {
			throw new \RuntimeException('S2S Result construction requires an unmodified scalar payload');
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
		$payload = self::require_scalar_payload($target, $role);
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

	/** Required-value consumers copy a checked payload; casts and other contexts remain separate. */
	private static function extract(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if (($context !== conversion_context::assignment) && ($context !== conversion_context::argument)
			&& ($context !== conversion_context::return_value)) {
			throw new \RuntimeException('S2S Result extraction supports only required assignment, argument and return boundaries');
		}
		$source_role = self::role($source);
		if ($source_role === null) {
			throw new \LogicException('Result extraction requires a Result source');
		}
		$payload = self::require_scalar_payload($source, $source_role);
		if (!$payload->matches($target)) {
			throw new \RuntimeException('S2S Result extraction requires the exact scalar payload type');
		}
		return Conversion_Preparation::operation($source, $target, $context, conversion_operation::result_extract);
	}
}
