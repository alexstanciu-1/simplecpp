<?php

/* Mixed value boundaries and explicit casts; payload checks remain runtime-owned. */
namespace scpp\compiler;

final class Mixed_Conversions
{
	public static function is_mixed(canonical_type_use $type): bool
	{
		return Type_Preparation::canonical($type)->definition() instanceof mixed_semantics;
	}

	/** The boxed integer is ordinary int; fixed-width normalization is a separate contract. */
	public static function is_scalar_payload(canonical_type_use $type): bool
	{
		if ($type->by_value()) {
			return false;
		}
		return $type->matches(Language_Types::integer(Model::$language_scope))
			|| $type->matches(Language_Types::boolean(Model::$language_scope))
			|| $type->matches(Language_Types::floating(Model::$language_scope))
			|| $type->matches(Language_Types::string_type(Model::$language_scope));
	}

	/** No operator-driven extraction, implicit reference adaptation or wrapper lifting. */
	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if ($context === conversion_context::explicit_cast) {
			if ($source->by_value() || !self::is_scalar_payload($target)) {
				throw new \RuntimeException('S2S explicit mixed casts require bool, ordinary int, float or string without storage modifiers');
			}
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::explicit_runtime_cast);
		}
		if (($context !== conversion_context::assignment) && ($context !== conversion_context::argument)
			&& ($context !== conversion_context::return_value)) {
			throw new \RuntimeException('S2S mixed conversion currently supports only assignment, argument and return value boundaries');
		}
		if ($source->by_value() || $target->by_value()) {
			throw new \RuntimeException('S2S mixed boundaries do not support effective storage modifiers');
		}
		if ($source->matches($target)) {
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::identity);
		}
		if (self::is_mixed($target))
		{
			if (Nullable_Conversions::is_null($source) || self::is_scalar_payload($source)) {
				return Conversion_Preparation::operation($source, $target, $context, conversion_operation::mixed_box);
			}
			throw new \RuntimeException('S2S mixed boxing currently supports null, bool, ordinary int, float and string');
		}
		if (self::is_scalar_payload($target)) {
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::mixed_extract);
		}
		throw new \RuntimeException('S2S mixed extraction currently supports required bool, ordinary int, float and string');
	}
}
