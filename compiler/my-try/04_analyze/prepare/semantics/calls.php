<?php

/* Selected callable contracts share one argument-admission path. */
namespace scpp\compiler;

/** Argument admission is independent of how a callable was selected. */
final class Call_Preparation
{
	/** Complete source prerequisites before reading the shared callable contract. */
	public static function require_signature(callable_target_i $target, preparation_context $context): callable_signature
	{
		if ($target instanceof collected_function) {
			$definition = object_cast($target, collected_function::class);
			$context->worker->require_declaration($context->owner, $definition);
		}
		elseif (!($target instanceof registered_static_factory) && !($target instanceof registered_function)) {
			throw new \LogicException('Unsupported callable target for preparation');
		}
		return $target->signature();
	}

	/** Bounded installed overload selection: exact fixed value signatures, without conversion ranking. */
	public static function prepare_named(array $targets /** vector<callable_definition_i> */,
		Storage $arguments /** Storage<expression_node> */, preparation_context $context): prepared_call
	{
		if (q_count($targets) === 1) {
			$target = $targets[0];
			if ($target instanceof compiler_function_definition) {
				return Compiler_Call_Preparation::prepare(object_cast($target, compiler_function_definition::class), $arguments, $context);
			}
			return self::prepare(object_cast($target, callable_target_i::class), $arguments, $context);
		}
		if (q_count($targets) === 0) {
			throw new \RuntimeException('S2S named call has no resolved function');
		}

		foreach ($targets as $candidate)
		{
			if (!($candidate instanceof registered_function)) {
				throw new \RuntimeException('S2S source function overloads are deferred');
			}
			$registered = object_cast($candidate, registered_function::class);
			$parameters /** Storage<callable_parameter> */ = $registered->signature()->parameters;
			foreach ($parameters as $parameter)
			{
				if (($parameter->mode !== passing_mode::value) || ($parameter->default_value !== null)
					|| ($parameter->element_type !== null)) {
					throw new \RuntimeException('S2S runtime overloads require fixed required value parameters');
				}
			}
		}

		// Prepare once without a candidate's contextual type; ambiguous literals need explicit targets.
		foreach ($arguments as $argument) {
			Expression_Preparation::prepare_for_consumer($argument, $context, null, expression_consumer::consuming_argument);
		}
		$matches /** vector<callable_target_i> */ = [];
		foreach ($targets as $candidate)
		{
			$registered_candidate = object_cast($candidate, registered_function::class);
			$parameters /** Storage<callable_parameter> */ = $registered_candidate->signature()->parameters;
			if (q_count($parameters) !== q_count($arguments)) {
				continue;
			}
			$matched = true;
			foreach ($arguments as $index => $argument)
			{
				if (!$argument->require_preparation()->type->matches($parameters[$index]->type)) {
					$matched = false;
					break;
				}
			}
			if ($matched) {
				$matches[] = $registered_candidate;
			}
		}
		if (q_count($matches) !== 1) {
			throw new \RuntimeException('S2S runtime call requires one exact signature; use explicit argument types');
		}
		return self::prepare($matches[0], $arguments, $context, true);
	}

	/** Admit one selected contract; exact-overload selection has already prepared its arguments. */
	public static function prepare(callable_target_i $target, Storage $arguments /** Storage<expression_node> */,
		preparation_context $context, bool $arguments_prepared = false): prepared_call
	{
		return self::admit($target, self::require_signature($target, $context), $arguments, $context, $arguments_prepared);
	}

	/** Concrete contracts share boundaries whether fixed or selected from compiler metadata. */
	public static function admit(callable_definition_i $target, callable_signature $signature,
		Storage $arguments /** Storage<expression_node> */, preparation_context $context,
		bool $arguments_prepared): prepared_call
	{
		$facts = new prepared_call();
		$facts->target = $target;
		$facts->signature = $signature;
		if ($facts->signature->reference_return !== null) {
			if (!($target instanceof collected_function)) {
				throw new \RuntimeException('S2S runtime reference-return calls await lifetime integration');
			}
		}
		$facts->type = $facts->signature->return_type;
		if ($facts->signature->reference_return === null)
		{
			$role = Ownership_Conversions::role($facts->type);
			if ($role !== null) {
				if ($role->ownership_family() === ownership_kind::unique) {
					$facts->value_origin = expression_value_origin::owned_call_result;
				}
			}
		}
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
			$parameter_index /** int */ = $facts->fixed_count;
			if ($index < $facts->fixed_count) { $parameter_index = $index; }
			$parameter = $parameters[$parameter_index];
			$argument_type = $parameter->element_type ?? $parameter->type;
			$consumer = expression_consumer::value;
			if (($parameter->mode === passing_mode::value) && ($parameter->element_type === null)) {
				$consumer = expression_consumer::consuming_argument;
			}
			$value /** prepared_expression */ = $arguments_prepared
				? $argument->require_preparation() : Expression_Preparation::prepare_for_consumer($argument, $context, $argument_type, $consumer);
			$prepared_argument = new prepared_call_argument();
			if ($parameter->mode === passing_mode::value) {
				$prepared_argument->conversion = Conversion_Preparation::decide(
					$value->type, $argument_type, conversion_context::argument);
			}
			$prepared_argument->boundary = Value_Boundary_Preparation::decide(
				$argument_type, $prepared_argument->conversion, $context, $value, $parameter->mode, $parameter->access);
			$prepared_arguments->append($prepared_argument);
		}

		if ($facts->signature->reference_return !== null) {
			$origin /** reference_return_contract */ = $facts->signature->reference_return;
			$facts->place = $arguments[$origin->parameter_index()]->require_preparation()->place;
		}
		return $facts;
	}
}
