<?php
namespace scpp\compiler;

/** Exact concrete/pattern selection shares one argument traversal and admission path. */
final class Runtime_Call_Preparation
{
	public static function prepare(array $targets /** vector<callable_definition_i> */,
		Storage $arguments /** Storage<expression_node> */, preparation_context $context,
		?canonical_type_use $runtime_owner = null): prepared_call
	{
		$observing = false;
		$consuming = false;
		foreach ($targets as $candidate) {
			if (!($candidate instanceof runtime_function_definition)) {
				throw new \RuntimeException('S2S source function overloads are deferred');
			}
			if (self::observes(object_cast($candidate, runtime_function_definition::class))) { $observing = true; }
			else { $consuming = true; }
		}
		if ($observing && $consuming) {
			throw new \RuntimeException('Runtime overloads cannot mix observation and consuming inputs');
		}
		$consumer = $observing ? expression_consumer::value : expression_consumer::consuming_argument;
		foreach ($arguments as $argument) {
			Expression_Preparation::prepare_for_consumer($argument, $context, null, $consumer);
		}
		$selected /** ?runtime_function_definition */ = null;
		$inference /** ?runtime_type_bindings */ = null;
		foreach ($targets as $candidate) {
			$runtime = object_cast($candidate, runtime_function_definition::class);
			$match = self::match($runtime, $arguments, $runtime_owner);
			if ($match === null) { continue; }
			if ($selected !== null) {
				throw new \RuntimeException('S2S runtime call requires one exact signature; ambiguous matching contracts');
			}
			$selected = $runtime;
			$inference = $match;
		}
		if ($selected === null) {
			throw new \RuntimeException('S2S runtime call requires one exact signature; use explicit argument types');
		}
		if ($inference === null) { throw new \LogicException('Selected runtime call lost inference'); }
		$signature /** callable_signature */;
		if ($selected instanceof registered_generic_function) {
			$signature = self::instantiate(object_cast($selected, registered_generic_function::class), $inference, $context);
		}
		else {
			$signature = object_cast($selected, registered_function::class)->signature();
		}
		$facts = Call_Preparation::admit($selected, $signature, $arguments, $context, true);
		$facts->runtime_owner = $runtime_owner;
		return $facts;
	}

	/** Determine expression consumption before visiting any argument. */
	private static function observes(runtime_function_definition $target): bool
	{
		if ($target instanceof registered_generic_function) {
			$contract = object_cast($target, registered_generic_function::class)->contract();
			foreach ($contract->parameters as $parameter) {
				if ($parameter->mode === passing_mode::observation) { return true; }
			}
			return false;
		}
		$signature = object_cast($target, registered_function::class)->signature();
		$observing = false;
		foreach ($signature->parameters as $input) {
			if ($input->mode === passing_mode::observation) { $observing = true; }
		}
		return $observing;
	}

	/** Pure structural matching cannot publish specializations or prepare source capabilities. */
	private static function match(runtime_function_definition $target, Storage $arguments /** Storage<expression_node> */,
		?canonical_type_use $runtime_owner): ?runtime_type_bindings
	{
		$result = new runtime_type_bindings();
		if ($target instanceof registered_generic_function) {
			$contract = object_cast($target, registered_generic_function::class)->contract();
			if ($contract->owner_parameter_count > 0) {
				if ($runtime_owner === null) { return null; }
				$owner = Type_Queries::canonical($runtime_owner);
				if (!($owner instanceof applied_template_type)) { return null; }
				$application = object_cast($owner, applied_template_type::class);
				$member = Model::$runtime_callables->member_for($target);
				if ($member === null) { return null; }
				if ($application->definition() !== $member->owner) { return null; }
				$result = Runtime_Pattern_Preparation::owner_arguments($runtime_owner);
			}
			$patterns /** Storage<runtime_pattern_parameter> */ = $contract->parameters;
			$fixed_count = q_count($patterns);
			$variadic = false;
			if ($fixed_count > 0) { $variadic = $patterns[$fixed_count - 1]->variadic; }
			if ($variadic) { $fixed_count--; }
			if (!$variadic && (q_count($arguments) > $fixed_count)) { return null; }
			for ($index = q_count($arguments); $index < $fixed_count; $index++) {
				if ($patterns[$index]->default_value === null) { return null; }
			}
			foreach ($arguments as $index => $argument) {
				$position = $index < $fixed_count ? $index : $fixed_count;
				if (!self::match_type($patterns[$position]->type, $argument->require_preparation()->type, $result)) { return null; }
			}
			if (q_count($result->bindings) !== $contract->type_parameter_count) { return null; }
			foreach ($patterns as $parameter) {
				if ($parameter->default_value !== null) {
					$default_value /** prepared_literal_parameter_default */ = $parameter->default_value;
					if (!self::match_type($parameter->type, $default_value->result_type(), $result)) { return null; }
				}
			}
			return $result;
		}
		$parameters /** Storage<callable_parameter> */ = object_cast($target, registered_function::class)->signature()->parameters;
		$fixed_count = q_count($parameters);
		$variadic = false;
		if ($fixed_count > 0) { $variadic = $parameters[$fixed_count - 1]->element_type !== null; }
		if ($variadic) { $fixed_count--; }
		if (!$variadic && (q_count($arguments) > $fixed_count)) { return null; }
		for ($index = q_count($arguments); $index < $fixed_count; $index++) {
			if ($parameters[$index]->default_value === null) { return null; }
		}
		foreach ($arguments as $index => $argument) {
			$position = $index < $fixed_count ? $index : $fixed_count;
			$parameter = $parameters[$position];
			$type = $parameter->element_type ?? $parameter->type;
			if (!$type->matches($argument->require_preparation()->type)) { return null; }
		}
		return $result;
	}

	/** Match canonical identities recursively; repeated variables must preserve exact storage. */
	private static function match_type(runtime_type_pattern $pattern, canonical_type_use $actual,
		runtime_type_bindings $inference): bool
	{
		if ($pattern instanceof runtime_concrete_pattern) {
			return object_cast($pattern, runtime_concrete_pattern::class)->type->matches($actual);
		}
		if ($pattern instanceof runtime_variable_pattern) {
			$index = object_cast($pattern, runtime_variable_pattern::class)->position;
			if (isset($inference->bindings[$index])) { return $inference->bindings[$index]->matches($actual); }
			$inference->bindings[$index] = $actual;
			return true;
		}
		if ($actual->by_value()) { return false; }
		$type = Model::$type_catalog->registry()->type($actual->type_id());
		if (!($type instanceof applied_template_type)) { return false; }
		$application = object_cast($type, applied_template_type::class);
		$expected = object_cast($pattern, runtime_application_pattern::class);
		if ($application->template_definition() !== $expected->definition) { return false; }
		$children = $application->arguments();
		if (q_count($children) !== q_count($expected->arguments)) { return false; }
		foreach ($expected->arguments as $index => $child) {
			if (!self::match_type($child, $children[$index], $inference)) { return false; }
		}
		return true;
	}

	/** Only the selected provider materializes types, with normal capability dependencies. */
	private static function instantiate(registered_generic_function $target, runtime_type_bindings $inference,
		preparation_context $context): callable_signature
	{
		$contract = $target->contract();
		if (q_count($inference->bindings) !== $contract->type_parameter_count) {
			throw new \LogicException('Incomplete runtime type inference');
		}
		$result = new callable_signature();
		$result->return_type = Runtime_Pattern_Preparation::substitute($contract->return_type, $inference, $context);
		$parameters /** Storage<callable_parameter> */ = $result->parameters;
		foreach ($contract->parameters as $pattern) {
			$type = Runtime_Pattern_Preparation::substitute($pattern->type, $inference, $context);
			$parameter = Declaration_Preparation::parameter_contract($type, $pattern->mode, $pattern->access,
				$pattern->variadic, $context);
			if ($pattern->default_value !== null) {
				$default_value /** prepared_literal_parameter_default */ = $pattern->default_value;
				if (!$default_value->result_type()->matches($parameter->type)) {
					throw new \RuntimeException('Runtime default must have the exact parameter type');
				}
				$parameter->default_value = $default_value;
			}
			$parameters->append($parameter);
		}
		Callable_Contracts::validate($result);
		return $result;
	}

}
