<?php

/* Shared condition admission; explicit boolean normalization has a separate owner. */
namespace scpp\compiler;

final class Condition_Truthiness
{
	/** Follow the existing scalar condition contract; broader carriers remain deferred. */
	public static function decide(canonical_type_use $source): truthiness_decision
	{
		if (!Conversion_Preparation::is_scalar($source)) {
			throw new \RuntimeException('S2S condition truthiness requires a supported scalar type');
		}
		$decision = new truthiness_decision();
		$decision->source_type = $source;
		$decision->operation = Type_Preparation::canonical($source)->family() === type_family::boolean
			? truthiness_operation::boolean_identity : truthiness_operation::scalar_condition;
		return $decision;
	}
}
