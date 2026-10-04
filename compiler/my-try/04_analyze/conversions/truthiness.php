<?php

/* Shared condition admission; explicit boolean normalization has a separate owner. */
namespace scpp\compiler;

final class Condition_Truthiness
{
	/** Wrapper conditions use their state/payload contract, never explicit bool casts. */
	public static function decide(canonical_type_use $source): truthiness_decision
	{
		if (Mixed_Conversions::is_mixed($source))
		{
			if ($source->by_value()) {
				throw new \RuntimeException('S2S mixed conditions do not support effective storage modifiers');
			}
			$decision = new truthiness_decision();
			$decision->source_type = $source;
			$decision->operation = truthiness_operation::mixed_condition;
			return $decision;
		}
		if ($source->matches(Language_Types::error_type(Model::$language_scope)))
		{
			$decision = new truthiness_decision();
			$decision->source_type = $source;
			$decision->operation = truthiness_operation::error_condition;
			return $decision;
		}
		$result_role = Result_Conversions::role($source);
		if ($result_role !== null) {
			Result_Conversions::require_scalar_payload($source, $result_role);
			$decision = new truthiness_decision();
			$decision->source_type = $source;
			$decision->operation = truthiness_operation::result_condition;
			return $decision;
		}
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
