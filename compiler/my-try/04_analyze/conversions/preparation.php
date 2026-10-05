<?php

/* Resolve one actual/expected type relationship into a retained semantic decision. */
namespace scpp\compiler;

final class Conversion_Preparation
{
	/** Exact canonical identity precedes target-family conversion policy. */
	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		Type_Preparation::require_value_type($target);
		if (($context === conversion_context::initializer_key) || ($context === conversion_context::container_element)) {
			if (Boxed_Conversions::is_boxed($source) || Boxed_Conversions::is_boxed($target)) {
				return Boxed_Conversions::container_input($source, $target, $context);
			}
			// Ordinary typed input follows the same boundaries as a by-value argument.
			$input = self::decide($source, $target, conversion_context::argument);
			$input->context = $context;
			return $input;
		}
		if ($context === conversion_context::concatenation) {
			return String_Conversions::decide_text($source, $target, $context);
		}
		if (($context === conversion_context::explicit_cast) && $target->by_value()) {
			throw new \RuntimeException('S2S explicit cast target cannot use a type-use modifier');
		}
		if (($context === conversion_context::explicit_cast) && !self::is_scalar($target)
			&& !Boxed_Conversions::is_family_crossing($source, $target)) {
			throw new \RuntimeException('S2S explicit cast target is not supported');
		}
		if (Boxed_Conversions::is_boxed($source) || Boxed_Conversions::is_boxed($target)) {
			return Boxed_Conversions::decide($source, $target, $context);
		}
		if ((Result_Conversions::role($source) !== null) || (Result_Conversions::role($target) !== null)) {
			return Result_Conversions::decide($source, $target, $context);
		}
		if ((Nullable_Conversions::payload($source) !== null) || (Nullable_Conversions::payload($target) !== null)
			|| Nullable_Conversions::is_null($source)) {
			return Nullable_Conversions::decide($source, $target, $context);
		}
		if ($source->matches($target)) {
			return self::operation($source, $target, $context, conversion_operation::identity);
		}
		if ($source->by_value() !== $target->by_value()) {
			throw new \RuntimeException('S2S value boundary requires matching type-use modifiers');
		}

		$target_family = Type_Queries::canonical($target)->family();
		if ($target_family === type_family::boolean) {
			return Boolean_Conversions::decide($source, $target, $context);
		}
		if ($target_family === type_family::integer) {
			return Integer_Conversions::decide($source, $target, $context);
		}
		if ($target_family === type_family::floating) {
			return Floating_Point_Conversions::decide($source, $target, $context);
		}
		if (($target_family === type_family::nominal) && String_Conversions::is_string($target)) {
			return String_Conversions::decide($source, $target, $context);
		}

		throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
	}

	/** Scalar cast permission is explicit and does not include arbitrary nominal types. */
	public static function is_scalar(canonical_type_use $type): bool
	{
		$family = Type_Queries::canonical($type)->family();
		return ($family === type_family::boolean) || ($family === type_family::integer)
			|| ($family === type_family::floating) || String_Conversions::is_string($type);
	}

	/** Family owners publish one compact decision without backend spelling. */
	public static function operation(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context, conversion_operation $operation): conversion_decision
	{
		$decision = new conversion_decision();
		$decision->source_type = $source;
		$decision->target_type = $target;
		$decision->result_type = $target;
		$decision->context = $context;
		$decision->operation = $operation;
		return $decision;
	}
}
