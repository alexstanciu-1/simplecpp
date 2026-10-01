<?php

/* Select conversions whose requested result is in the canonical integer family. */
namespace scpp\compiler;

final class Integer_Conversions
{
	/** Preserve the current integer-boundary contract while centralizing its selection. */
	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if ($context === conversion_context::explicit_cast) {
			if (Conversion_Preparation::is_scalar($source)) {
				return Conversion_Preparation::operation(
					$source, $target, $context, conversion_operation::explicit_runtime_cast);
			}
			throw new \RuntimeException('S2S explicit integer cast is not supported for this source type');
		}
		if (Type_Preparation::canonical($source)->family() === type_family::integer) {
			return Conversion_Preparation::operation(
				$source, $target, $context, conversion_operation::integer_value_cast);
		}

		throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
	}
}
