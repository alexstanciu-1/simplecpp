<?php
namespace scpp\compiler;

/** Exact selection precedes bounded required-payload fallback; both share ordinary argument admission. */
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
		$selected /** nullable<runtime_function_definition> */ = null;
		$inference /** nullable<runtime_type_bindings> */ = null;
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
			$selected = self::select_required_payload($targets, $arguments);
			$inference = new runtime_type_bindings();
		}
		if ($selected === null) {
			throw new \RuntimeException('S2S runtime call requires one exact signature or a unique checked-payload signature; use explicit argument types');
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

	/** A zero-exact-match fallback never ranks conversions or instantiates generic recipes. */
	private static function select_required_payload(array $targets /** vector<callable_definition_i> */,
		Storage $arguments /** Storage<expression_node> */): ?registered_function
	{
		$selected /** nullable<registered_function> */ = null;
		foreach ($targets as $candidate)
		{
			if (!($candidate instanceof registered_function)) {
				continue;
			}
			$concrete = object_cast($candidate, registered_function::class);
			if (!self::matches_required_arguments($concrete->signature(), $arguments)) {
				continue;
			}
			if ($selected !== null) {
				throw new \RuntimeException('S2S runtime call has ambiguous checked-payload signatures; use explicit argument types');
			}
			$selected = $concrete;
		}
		return $selected;
	}

	/** Fixed concrete value inputs may extract once; references and observations remain exact. */
	private static function matches_required_arguments(callable_signature $signature,
		Storage $arguments /** Storage<expression_node> */): bool
	{
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		if (q_count($parameters) !== q_count($arguments)) {
			return false;
		}
		$extracts = false;
		foreach ($parameters as $index => $parameter)
		{
			if (($parameter->default_value !== null) || ($parameter->element_type !== null)) {
				return false;
			}
			$actual = $arguments[$index]->require_preparation()->type;
			if ($parameter->type->matches($actual)) {
				continue;
			}
			if (($parameter->mode !== passing_mode::value)
				|| !Conversion_Preparation::matches_required_payload($actual, $parameter->type)) {
				return false;
			}
			$extracts = true;
		}
		return $extracts;
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
			if ($variadic) { $fixed_count -= 1; }
			if (!$variadic && (q_count($arguments) > $fixed_count)) { return null; }
			for ($index = q_count($arguments); $index < $fixed_count; $index++) {
				if ($patterns[$index]->default_value === null) { return null; }
			}
			foreach ($arguments as $index => $argument) {
				$position /** int */ = $fixed_count;
				if ($index < $fixed_count) { $position = $index; }
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
		if ($variadic) { $fixed_count -= 1; }
		if (!$variadic && (q_count($arguments) > $fixed_count)) { return null; }
		for ($index = q_count($arguments); $index < $fixed_count; $index++) {
			if ($parameters[$index]->default_value === null) { return null; }
		}
		foreach ($arguments as $index => $argument) {
			$position /** int */ = $fixed_count;
			if ($index < $fixed_count) { $position = $index; }
			$parameter = $parameters[$position];
			$type = $parameter->element_type ?? $parameter->type;
			if (!self::match_concrete($type, $argument->require_preparation()->type)) { return null; }
		}
		return $result;
	}

	/** Match canonical identities recursively; repeated variables must preserve exact storage. */
	private static function match_type(runtime_type_pattern $pattern, canonical_type_use $actual,
		runtime_type_bindings $inference): bool
	{
		if ($pattern instanceof runtime_concrete_pattern) {
			return self::match_concrete(object_cast($pattern, runtime_concrete_pattern::class)->type, $actual);
		}
		if ($pattern instanceof runtime_variable_pattern) {
			$index = object_cast($pattern, runtime_variable_pattern::class)->position;
			if (isset($inference->bindings[$index])) { return $inference->bindings[$index]->matches($actual); }
			$inference->bindings[$index] = $actual;
			return true;
		}
		if ($pattern instanceof runtime_callable_pattern) {
			$callable_pattern = object_cast($pattern, runtime_callable_pattern::class);
			$signature = self::signature_for_type($actual);
			if ($signature === null) { return false; }
			if ($signature->reference_return !== null) { return false; }
			$parameters /** Storage<callable_parameter> */ = $signature->parameters;
			if (q_count($parameters) !== q_count($callable_pattern->parameters)) { return false; }
			foreach ($callable_pattern->parameters as $index => $parameter) {
				$provided = $parameters[$index];
				if (($provided->mode !== $parameter->mode) || ($provided->access !== $parameter->access)
					|| ($provided->default_value !== null) || (($provided->element_type !== null) !== $parameter->variadic)) { return false; }
				if (!self::match_type($parameter->type, $provided->element_type ?? $provided->type, $inference)) { return false; }
			}
			return self::match_type($callable_pattern->return_type, $signature->return_type, $inference);
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

	private static function match_concrete(canonical_type_use $expected, canonical_type_use $actual): bool
	{
		if ($expected->matches($actual)) { return true; }
		$definition = Type_Queries::canonical($expected)->definition();
		if (!($definition instanceof callable_type_definition)) { return false; }
		$signature = self::signature_for_type($actual);
		if ($signature === null) { return false; }
		return Callable_Contracts::matches_erased($signature, object_cast($definition, callable_type_definition::class)->signature());
	}

	/** Argument preparation already completed anonymous signatures; matching reads settled facts. */
	private static function signature_for_type(canonical_type_use $type): ?callable_signature
	{
		$definition = Type_Queries::canonical($type)->definition();
		if ($definition instanceof callable_type_definition) { return object_cast($definition, callable_type_definition::class)->signature(); }
		if ($definition instanceof closure_type_definition) { return Model::$type_catalog->closures()->identity($type)->signature(); }
		return null;
	}

	/** Only the selected provider materializes types, with normal capability dependencies. */
	private static function instantiate(registered_generic_function $target, runtime_type_bindings $inference,
		preparation_context $context): callable_signature
	{
		$contract = $target->contract();
		if (q_count($inference->bindings) !== $contract->type_parameter_count) {
			throw new \LogicException('Incomplete runtime type inference');
		}
		foreach ($contract->replacement_results as $requirement) {
			$result_type = $inference->bindings[$requirement->result_position];
			$replacement_type = $inference->bindings[$requirement->replacement_position];
			if ((Type_Queries::canonical($replacement_type)->family() !== type_family::no_value)
				&& !$replacement_type->matches($result_type)) {
				throw new \RuntimeException('Runtime replacement result must be void or exactly match the worker result type');
			}
		}
		foreach ($contract->boxed_results as $boxed_result) {
			Transfer_Preparation::require_boxed_result($inference->bindings[$boxed_result->result_position], $boxed_result->family_type);
		}
		foreach ($contract->transfer_positions as $position) {
			Transfer_Preparation::require_payload($inference->bindings[$position]);
		}
		$result = new callable_signature();
		$result->return_type = Runtime_Pattern_Preparation::substitute($contract->return_type, $inference, $context);
		$parameters /** Storage<callable_parameter> */ = $result->parameters;
		foreach ($contract->parameters as $pattern) {
			$type = Runtime_Pattern_Preparation::substitute($pattern->type, $inference, $context);
			// Explicit provider sentinel slots are not source variable declarations.
			$parameter = new callable_parameter();
			if (Type_Queries::canonical($type)->family() === type_family::null_sentinel) {
				if (($pattern->mode !== passing_mode::value) || $pattern->variadic) {
					throw new \RuntimeException('Runtime null slots require fixed value passing');
				}
				$parameter->type = $type;
				$parameter->mode = $pattern->mode;
				$parameter->access = $pattern->access;
			}
			else {
				$parameter = Declaration_Preparation::parameter_contract($type, $pattern->mode, $pattern->access,
					$pattern->variadic, $context);
			}
			$parameter->requires_copy = $pattern->requires_copy;
			$parameter->requires_transfer = $pattern->requires_transfer;
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
