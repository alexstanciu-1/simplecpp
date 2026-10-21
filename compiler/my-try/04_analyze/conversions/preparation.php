<?php

/* Resolve one actual/expected type relationship into a retained semantic decision. */
namespace scpp\compiler;

final class Conversion_Preparation
{
	/** Exact canonical identity precedes target-family conversion policy. */
	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context, ?conversion_relationship_query $relationships = null): conversion_decision
	{
		// Registered providers may expose a fixed null sentinel argument slot.
		// Source declarations still reject null storage before reaching this boundary.
		if (($context === conversion_context::argument) && Nullable_Conversions::is_null($target)
			&& $source->matches($target)) {
			return self::operation($source, $target, $context, conversion_operation::identity);
		}
		Type_Preparation::require_value_type($target);
		if (($context === conversion_context::initializer_key) || ($context === conversion_context::container_element)) {
			if (Boxed_Conversions::is_boxed($source) || Boxed_Conversions::is_boxed($target)) {
				return Boxed_Conversions::container_input($source, $target, $context);
			}
			// Ordinary typed input follows the same boundaries as a by-value argument.
			$input = self::decide($source, $target, conversion_context::argument, $relationships);
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
		if ((Result_Conversions::role($source) !== null) || (Result_Conversions::role($target) !== null)) {
			return Result_Conversions::decide($source, $target, $context);
		}
		if ((Ownership_Conversions::role($source) !== null) || (Ownership_Conversions::role($target) !== null)) {
			return Ownership_Conversions::decide($source, $target, $context, $relationships);
		}
		if (Boxed_Conversions::is_boxed($source) || Boxed_Conversions::is_boxed($target)) {
			return Boxed_Conversions::decide($source, $target, $context);
		}
		if ((Nullable_Conversions::payload($source) !== null) || (Nullable_Conversions::payload($target) !== null)
			|| Nullable_Conversions::is_null($source)) {
			return Nullable_Conversions::decide($source, $target, $context);
		}
		if ($source->matches($target)) {
			return self::operation($source, $target, $context, conversion_operation::identity);
		}
		$target_definition = Type_Queries::canonical($target)->definition();
		if ($target_definition instanceof callable_type_definition) {
			if ($relationships !== null) {
				if ($relationships->is_callable_compatible($source, object_cast($target_definition, callable_type_definition::class))) {
					return self::operation($source, $target, $context, conversion_operation::callable_wrap);
				}
			}
			throw new \RuntimeException('Callable boundary requires an exact parameter and return signature');
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

	/**
	 * Structural eligibility for one required-payload boundary, without preparing
	 * capabilities or retaining conversion facts for an unselected overload.
	 * Selected arguments still pass through decide() and normal copy admission.
	 */
	public static function matches_required_payload(canonical_type_use $source, canonical_type_use $target): bool
	{
		if ($source->by_value() || (Result_Conversions::role($target) !== null)
			|| (Nullable_Conversions::payload($target) !== null)) {
			return false;
		}
		$nullable_payload = Nullable_Conversions::payload($source);
		if ($nullable_payload !== null) {
			return $nullable_payload->matches($target);
		}
		$result_role = Result_Conversions::role($source);
		if ($result_role === null) {
			return false;
		}
		$application = object_cast(Type_Queries::canonical($source), applied_template_type::class);
		return $result_role->payload_type($application)->matches($target);
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
