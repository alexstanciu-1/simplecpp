<?php

/* Mixed value boundaries and explicit casts; payload checks remain runtime-owned. */
namespace scpp\compiler;

final class Mixed_Conversions
{
	/** Container elements admit dynamic boxing; unknown mixed key kinds stay runtime checked. */
	public static function container_input(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if ($context === conversion_context::initializer_key) {
			if (self::is_mixed($target)) {
				if (!self::is_mixed($source) && !String_Conversions::is_string($source)
					&& !$source->matches(Language_Types::integer(Model::$language_scope))) {
					throw new \RuntimeException('S2S mixed initializer keys require int, string or runtime-checked mixed');
				}
			}
		}
		elseif ($context === conversion_context::container_element) {
			if (self::is_mixed($target) && !($source->by_value() || $target->by_value())) {
				if (Type_Queries::canonical($source)->definition() instanceof dynamic_semantics) {
					return Conversion_Preparation::operation($source, $target, $context, conversion_operation::mixed_dynamic_box);
				}
			}
		}
		else { throw new \LogicException('Expected container input conversion context'); }
		$decision = self::decide($source, $target, conversion_context::argument);
		$decision->context = $context;
		return $decision;
	}

	/** Explicit shared-handle bridges are distinct from ordinary scalar boxing. */
	public static function is_dynamic_bridge(canonical_type_use $source, canonical_type_use $target): bool
	{
		$source_dynamic = Type_Queries::canonical($source)->definition() instanceof dynamic_semantics;
		$target_dynamic = Type_Queries::canonical($target)->definition() instanceof dynamic_semantics;
		return (self::is_mixed($source) && $target_dynamic) || ($source_dynamic && self::is_mixed($target));
	}

	public static function is_mixed(canonical_type_use $type): bool
	{
		return Type_Queries::canonical($type)->definition() instanceof mixed_semantics;
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
		if ($context === conversion_context::operator_operand)
		{
			if (!$source->by_value() && !$target->by_value() && self::is_mixed($target))
			{
				if ($source->matches($target)) {
					return Conversion_Preparation::operation($source, $target, $context, conversion_operation::identity);
				}
				// The operator owner validates its numeric/equality domain before normalization.
				if (self::is_scalar_payload($source) || Nullable_Conversions::is_null($source)) {
					return Conversion_Preparation::operation($source, $target, $context, conversion_operation::mixed_box);
				}
			}
			throw new \RuntimeException('S2S mixed operator normalization requires matching mixed, scalar or null operands');
		}
		if (($context === conversion_context::conditional_branch) || ($context === conversion_context::coalesce_value))
		{
			if (!$source->by_value() && !$target->by_value() && self::is_mixed($target))
			{
				if ($source->matches($target)) {
					return Conversion_Preparation::operation($source, $target, $context, conversion_operation::identity);
				}
				if (self::is_scalar_payload($source)) {
					return Conversion_Preparation::operation($source, $target, $context, conversion_operation::mixed_box);
				}
			}
			throw new \RuntimeException('S2S mixed selection requires matching mixed or supported scalar branches');
		}
		if ($context === conversion_context::explicit_cast) {
			if (!$source->by_value() && !$target->by_value() && self::is_dynamic_bridge($source, $target)) {
				return Conversion_Preparation::operation($source, $target, $context,
					self::is_mixed($target) ? conversion_operation::mixed_dynamic_box : conversion_operation::explicit_runtime_cast);
			}
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
