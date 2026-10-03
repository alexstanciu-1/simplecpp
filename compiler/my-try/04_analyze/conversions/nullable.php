<?php

/* Nullable role queries and bounded value-boundary policy; no spelling-based admission. */
namespace scpp\compiler;

final class Nullable_Conversions
{
	public static function is_null(canonical_type_use $type): bool
	{
		return Type_Preparation::canonical($type)->family() === type_family::null_sentinel;
	}

	public static function payload(canonical_type_use $type): ?canonical_type_use
	{
		$canonical = Type_Preparation::canonical($type);
		$definition = $canonical->definition();
		if (!($definition instanceof nullable_semantics)) { return null; }
		$role = object_cast($definition, nullable_semantics::class);
		return $role->payload_type(object_cast($canonical, applied_template_type::class));
	}

	public static function require_scalar_payload(canonical_type_use $payload): void
	{
		if ($payload->by_value() || !Conversion_Preparation::is_scalar($payload)) {
			throw new \RuntimeException('S2S nullable value operations require a scalar payload');
		}
	}

	/** Only exact payload boundaries; cross-payload lifting remains a separate slice. */
	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		$source_payload = self::payload($source);
		$target_payload = self::payload($target);
		if ($source->by_value() || $target->by_value()) {
			throw new \RuntimeException('S2S nullable boundaries do not support storage modifiers');
		}
		if ($source_payload !== null) { self::require_scalar_payload($source_payload); }
		if ($target_payload !== null) {
			self::require_scalar_payload($target_payload);
			if (self::is_null($source)) {
				return Conversion_Preparation::operation($source, $target, $context, conversion_operation::nullable_empty);
			}
			if ($source->matches($target)) {
				return Conversion_Preparation::operation($source, $target, $context, conversion_operation::identity);
			}
			if ($source->matches($target_payload)) {
				return Conversion_Preparation::operation($source, $target, $context, conversion_operation::nullable_wrap);
			}
		}
		elseif ($source_payload !== null) {
			if ($source_payload->matches($target)) {
				// Explicit casts retain the runtime's string exception; required boundaries never do.
				$operation = $context === conversion_context::explicit_cast
					? conversion_operation::explicit_runtime_cast : conversion_operation::nullable_extract;
				return Conversion_Preparation::operation($source, $target, $context, $operation);
			}
		}
		throw new \RuntimeException('S2S nullable boundary requires the same payload type');
	}
}
