<?php

/* Select conversions whose requested result is in the canonical integer family. */
namespace scpp\compiler;

final class Integer_Conversions
{
	/** Preserve the current integer-boundary contract while centralizing its selection. */
	public static function decide(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context): conversion_decision
	{
		if ((Type_Preparation::canonical($source)->family() !== type_family::integer)
			|| (Type_Preparation::canonical($target)->family() !== type_family::integer)) {
			throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
		}

		$decision = new conversion_decision();
		$decision->source_type = $source;
		$decision->target_type = $target;
		$decision->result_type = $target;
		$decision->context = $context;
		$decision->operation = conversion_operation::integer_value_cast;
		return $decision;
	}
}
