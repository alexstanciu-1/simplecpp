<?php

/* Selected callable contracts share one argument-admission path. */
namespace scpp\compiler;

/** Argument admission is independent of how a callable was selected. */
final class Call_Preparation
{
	/** Complete source prerequisites before reading the shared callable contract. */
	public static function require_signature(callable_target_i $target, preparation_context $context): callable_signature
	{
		if ($target instanceof anonymous_callable_identity) {
			$identity = object_cast($target, anonymous_callable_identity::class);
			$context->worker->prepare_anonymous($identity, $context->owner);
		}
		elseif ($target instanceof collected_source_callable) {
			$definition = object_cast($target, collected_source_callable::class);
			$context->worker->require_declaration($context->owner, $definition);
		}
		elseif (!($target instanceof registered_static_factory) && !($target instanceof registered_function) && !($target instanceof callable_type_definition)) {
			throw new \LogicException('Unsupported callable target for preparation');
		}
		return $target->signature();
	}

	/** Bounded installed overload selection: exact fixed signatures, without conversion ranking. */
	public static function prepare_named(array $targets /** vector<callable_definition_i> */,
		Storage $arguments /** Storage<expression_node> */, preparation_context $context,
		?canonical_type_use $runtime_owner = null): prepared_call
	{
		if (q_count($targets) === 1) {
			$target = $targets[0];
			if ($target instanceof compiler_function_definition) {
				return Compiler_Call_Preparation::prepare(object_cast($target, compiler_function_definition::class), $arguments, $context);
			}
			if ($target instanceof registered_generic_function) {
				return Runtime_Call_Preparation::prepare($targets, $arguments, $context, $runtime_owner);
			}
			return self::prepare(object_cast($target, callable_target_i::class), $arguments, $context);
		}
		if (q_count($targets) === 0) {
			throw new \RuntimeException('S2S named call has no resolved function');
		}

		return Runtime_Call_Preparation::prepare($targets, $arguments, $context, $runtime_owner);
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
		$facts->target = weak($target);
		$facts->signature = $signature;
		if ($facts->signature->reference_return !== null) {
			if (!($target instanceof collected_source_callable) && !($target instanceof anonymous_callable_identity)
				&& !($target instanceof callable_type_definition)) {
				throw new \RuntimeException('S2S runtime reference-return calls await lifetime integration');
			}
		}
		$facts->type = $facts->signature->return_type;
		if ($facts->signature->reference_return === null)
		{
			Type_Lifecycle_Preparation::require_storage($facts->type, $context);
			$role = Ownership_Conversions::role($facts->type);
			if ($role !== null) {
				if ($role->ownership_family() === ownership_kind::unique) {
					$facts->value_origin = expression_value_origin::owned_call_result;
				}
			}
		}
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		// Provider requirements apply to the selected input type, including moved/defaulted inputs.
		foreach ($parameters as $parameter)
		{
			if ($parameter->requires_copy) {
				Capability_Preparation::require_copy($parameter->type, $context);
			}
		}
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		$facts->fixed_count = q_count($parameters);
		if ($facts->fixed_count > 0) {
			$last = $parameters[$facts->fixed_count - 1];
			if ($last->element_type !== null) {
				$facts->variadic_parameter = $last;
				$facts->fixed_count -= 1;
				// Packs use the same registered list construction contract as vector literals.
				Initialization_Preparation::binding($last->type, initialization_shape::positional, $context);
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
			if ($parameter->mode === passing_mode::observation) {
				if (!($target instanceof runtime_function_definition)) {
					throw new \RuntimeException('Observation parameters belong to registered runtime contracts');
				}
				Callable_Contracts::validate($signature);
			}
			$argument_type = $parameter->element_type ?? $parameter->type;
			$consumer = expression_consumer::value;
			if (($parameter->mode === passing_mode::value) && ($parameter->element_type === null)) {
				$consumer = expression_consumer::consuming_argument;
			}
			if ($arguments_prepared && ($consumer === expression_consumer::value) && ($argument instanceof move_expression_node)) {
				throw new \RuntimeException('S2S move requires an approved consuming argument boundary');
			}
			$value /** prepared_expression */ = $arguments_prepared
				? $argument->require_preparation() : Expression_Preparation::prepare_for_consumer($argument, $context, $argument_type, $consumer);
			if ($parameter->requires_transfer) { Capture_Preparation::require_transfer($value->type, $context); }
			$prepared_argument = new prepared_call_argument();
			$prepared_argument->source_index = $index;
			if ($parameter->mode === passing_mode::value) {
				$prepared_argument->conversion = Conversion_Preparation::decide(
					$value->type, $argument_type, conversion_context::argument, new Conversion_Relationships($context));
			}
			$prepared_argument->boundary = Value_Boundary_Preparation::decide(
				$argument_type, $prepared_argument->conversion, $context, $value, $parameter->mode, $parameter->access);
			$prepared_arguments->append($prepared_argument);
		}

		Call_Snapshot_Preparation::prepare($facts, $arguments);

		if ($facts->signature->reference_return !== null) {
			$origin /** reference_return_contract */ = $facts->signature->reference_return;
			// A reference result needs caller storage, never a call-local default owner.
			if ($origin->parameter_index() >= q_count($arguments)) {
				throw new \RuntimeException('Reference return cannot escape omitted default storage');
			}
			$facts->place = $arguments[$origin->parameter_index()]->require_preparation()->place;
		}
		return $facts;
	}
}
