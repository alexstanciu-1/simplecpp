<?php

/* Select explicit casts and text-context conversions to the runtime string type. */
namespace scpp\compiler;

final class String_Conversions
{
	/** Text contexts stringify values; they do not perform required string extraction. */
	public static function decide_text(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if (!self::is_string($target) || $source->by_value() || $target->by_value()) {
			throw new \RuntimeException('S2S concatenation requires unmodified string destination and value operands');
		}
		if ($source->matches($target)) {
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::identity);
		}
		if (self::is_string_carrier($source)) {
			// Carrier text follows the explicit cast contract, not the PHP display adapter.
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::explicit_runtime_cast);
		}
		if (!Boxed_Conversions::is_boxed($source) && !Boxed_Conversions::is_scalar_payload($source)
			&& !Nullable_Conversions::is_null($source)) {
			throw new \RuntimeException('S2S concatenation requires boxed, ordinary scalar or null operands');
		}
		return Conversion_Preparation::operation($source, $target, $context, conversion_operation::text_conversion);
	}

	/** Only the four registered string carriers participate in this text domain. */
	public static function is_string_carrier(canonical_type_use $source): bool
	{
		if ($source->by_value()) { return false; }
		$payload = Nullable_Conversions::payload($source);
		if ($payload !== null) { return self::is_string($payload); }
		$role = Result_Conversions::role($source);
		if ($role === null) { return false; }
		if (($role->result_family() !== result_kind::structured_error)
			&& ($role->result_family() !== result_kind::false_sentinel)
			&& ($role->result_family() !== result_kind::boolean_sentinels)) { return false; }
		return self::is_string(Result_Conversions::require_payload($source, $role));
	}

	public static function is_string(canonical_type_use $type): bool
	{
		return $type->matches(Language_Types::string_type(Model::$language_scope));
	}

	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if ((($context === conversion_context::explicit_cast) || ($context === conversion_context::interpolation))
			&& Conversion_Preparation::is_scalar($source)) {
			return Conversion_Preparation::operation(
				$source, $target, $context, conversion_operation::explicit_runtime_cast);
		}

		if ($context !== conversion_context::explicit_cast) {
			throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
		}
		throw new \RuntimeException('S2S explicit string cast is not supported for this source type');
	}
}
