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
		if ($source->matches($target)) {
			return self::identity($source, $target, $context);
		}
		if ($source->by_value() !== $target->by_value()) {
			throw new \RuntimeException('S2S value boundary requires matching type-use modifiers');
		}
		if (Type_Preparation::canonical($target)->family() === type_family::integer) {
			return Integer_Conversions::decide($source, $target, $context);
		}

		throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
	}

	private static function identity(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		$decision = new conversion_decision();
		$decision->source_type = $source;
		$decision->target_type = $target;
		$decision->result_type = $target;
		$decision->context = $context;
		$decision->operation = conversion_operation::identity;
		return $decision;
	}
}
