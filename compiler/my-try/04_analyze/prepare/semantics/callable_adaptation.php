<?php

/* Directional contract compatibility, independent of member lookup and C++ slots. */
namespace scpp\compiler;

final class Callable_Adaptation
{
	/** Source admission remains exact until dispatch bridges consume adaptation facts. */
	public static function exact(callable_signature $required, callable_signature $provided): bool
	{
		return self::between($required, $provided) !== null;
	}

	/** An observed relationship query enables only shared-value ancestry adaptation. */
	public static function between(callable_signature $required, callable_signature $provided,
		?conversion_relationship_query $relationships = null): ?prepared_callable_adaptation
	{
		if (($required->reference_return === null) !== ($provided->reference_return === null)) { return null; }
		if ($required->reference_return !== null) {
			if ($required->reference_return->parameter_index() !== $provided->reference_return->parameter_index()) { return null; }
		}
		$result = self::conversion($provided->return_type, $required->return_type,
			conversion_context::return_value, $required->reference_return === null, $relationships);
		if ($result === null) { return null; }
		$before /** Storage<callable_parameter> */ = $required->parameters;
		$after /** Storage<callable_parameter> */ = $provided->parameters;
		if ($before->count() !== $after->count()) { return null; }
		$facts = new prepared_callable_adaptation();
		$facts->result = $result;
		foreach ($before as $index => $parameter) {
			$current = $after[$index];
			if (($parameter->mode !== $current->mode) || ($parameter->access !== $current->access)
				|| (($parameter->element_type === null) !== ($current->element_type === null))
				|| (($parameter->default_value === null) !== ($current->default_value === null))) { return null; }
			if ($parameter->element_type !== null) {
				if (!$parameter->element_type->matches($current->element_type)) { return null; }
			}
			$conversion = self::conversion($parameter->type, $current->type, conversion_context::argument,
				($parameter->mode === passing_mode::value) && ($parameter->element_type === null), $relationships);
			if ($conversion === null) { return null; }
			$facts->parameters[] = $conversion;
		}
		return $facts;
	}

	private static function conversion(canonical_type_use $source, canonical_type_use $target,
		conversion_context $context, bool $allow_shared, ?conversion_relationship_query $relationships): ?conversion_decision
	{
		if ($source->matches($target)) {
			return Conversion_Preparation::operation($source, $target, $context, conversion_operation::identity);
		}
		if (!$allow_shared || ($relationships === null)) { return null; }
		$source_role = Ownership_Conversions::role($source);
		$target_role = Ownership_Conversions::role($target);
		if (($source_role === null) || ($target_role === null)) { return null; }
		if (($source_role->ownership_family() !== ownership_kind::shared)
			|| ($target_role->ownership_family() !== ownership_kind::shared)) { return null; }
		if (!$relationships->is_subtype(Ownership_Conversions::payload($source), Ownership_Conversions::payload($target))) { return null; }
		return Conversion_Preparation::operation($source, $target, $context, conversion_operation::ownership_upcast);
	}
}
