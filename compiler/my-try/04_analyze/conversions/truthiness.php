<?php

/* Shared condition admission; explicit boolean normalization has a separate owner. */
namespace scpp\compiler;

final class Condition_Truthiness
{
	/** Nullable conditions delegate to their scalar payload, never to explicit bool casts. */
	public static function decide(canonical_type_use $source): truthiness_decision
	{
		$payload = Nullable_Conversions::payload($source);
		if ($payload !== null) {
			Nullable_Conversions::require_scalar_payload($payload);
			if ($source->by_value()) { throw new \RuntimeException('S2S nullable conditions do not support storage modifiers'); }
			$decision = new truthiness_decision();
			$decision->source_type = $source;
			$decision->operation = truthiness_operation::nullable_condition;
			return $decision;
		}
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
