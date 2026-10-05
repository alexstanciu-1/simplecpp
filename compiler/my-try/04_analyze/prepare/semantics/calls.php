<?php

/* Selected callable contracts share one argument-admission path. */
namespace scpp\compiler;

/** Argument admission is independent of how a callable was selected. */
final class Call_Preparation
{
	public static function prepare(callable_target_i $target, Storage $arguments /** Storage<expression_node> */,
		preparation_context $context): prepared_call
	{
		$facts = new prepared_call();
		$facts->target = $target;
		$facts->signature = $target->require_signature($context);
		$facts->type = $facts->signature->return_type;
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		$facts->fixed_count = q_count($parameters);
		if ($facts->fixed_count > 0) {
			$last = $parameters[$facts->fixed_count - 1];
			if ($last->element_type !== null) {
				$facts->variadic_parameter = $last;
				$facts->fixed_count -= 1;
			}
		}
		if (($facts->variadic_parameter === null) && (q_count($arguments) > $facts->fixed_count)) {
			throw new \RuntimeException('S2S call argument count does not match its signature');
		}
		// Only fixed parameters need explicit arguments or defaults; the pack may be empty.
		for ($index = q_count($arguments); $index < $facts->fixed_count; $index++) {
			if ($parameters[$index]->default_value === null) {
				throw new \RuntimeException('S2S call is missing a required argument');
			}
		}

		// Prepare arguments in source order; references must preserve the selected storage.
		foreach ($arguments as $index => $argument)
		{
			$value = Expression_Preparation::prepare($argument, $context);
			$parameter_index /** int */ = $facts->fixed_count;
			if ($index < $facts->fixed_count) { $parameter_index = $index; }
			$parameter = $parameters[$parameter_index];
			$argument_type = $parameter->element_type ?? $parameter->type;
			$prepared_argument = new prepared_call_argument();
			if ($parameter->mode === passing_mode::reference) {
				if ((!$value->addressable) || !Type_Preparation::same_storage_type($value->type, $parameter->type)) {
					throw new \RuntimeException('S2S reference arguments require stable storage of the exact parameter type');
				}
			}
			else {
				$prepared_argument->conversion = Conversion_Preparation::decide(
					$value->type, $argument_type, conversion_context::argument);
				Capability_Preparation::require_copy($argument_type, $context);
			}
			$prepared_arguments->append($prepared_argument);
		}

		return $facts;
	}
}
