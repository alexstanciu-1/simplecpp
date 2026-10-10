<?php
namespace scpp\compiler;

/** Install registry-resolved recipes; matching source arguments belongs to preparation. */
final class Runtime_Catalog_Patterns
{
	public static function owner_positions(semantic_type_definition $owner): array /** hash<int> */
	{
		$positions /** hash<int> */ = [];
		if ($owner instanceof abstract_template_type_definition) {
			$recipe = object_cast($owner, abstract_template_type_definition::class);
			for ($index = 0; $index < $recipe->arity(); $index++) {
				$positions[$recipe->parameter($index)->name()] = $index;
			}
		}
		return $positions;
	}
	/** Every variable must be declared once and inferable from an input pattern. */
	public static function contract(runtime_function_spec $spec, registered_type_catalog $types): runtime_generic_contract
	{
		$positions /** hash<int> */ = [];
		$owner_count = 0;
		if ($spec->member !== null) {
			$membership /** runtime_callable_member_spec */ = $spec->member;
			$owner = Runtime_Catalog_Registration::member_definition($membership->owner, $types);
			$positions = self::owner_positions($owner);
			$owner_count = q_count($positions);
		}
		foreach ($spec->type_parameters as $name) {
			if (isset($positions[$name])) { throw new \RuntimeException('Duplicate runtime type parameter'); }
			$positions[$name] = q_count($positions);
		}
		$result = new runtime_generic_contract();
		$result->type_parameter_count = q_count($positions);
		$result->owner_parameter_count = $owner_count;
		$parameters /** Storage<runtime_pattern_parameter> */ = $result->parameters;
		$optional_seen = false;
		$variadic_seen = false;
		foreach ($spec->parameters as $input) {
			if ($variadic_seen) { throw new \RuntimeException('Runtime variadic parameter must be last'); }
			$variadic_seen = $input->variadic;
			if ($input->variadic) {
				// The language's packed-parameter ABI requires its registered vector definition.
				object_cast($types->definition('vector'), abstract_template_type_definition::class);
			}
			if ($input->variadic && (($input->mode !== passing_mode::value) || ($input->default_value !== null))) {
				throw new \RuntimeException('Runtime variadic parameters require value passing without an explicit default');
			}
			if ($input->mode === passing_mode::observation) {
				if ((q_count($spec->parameters) !== 1) || ($input->access !== storage_access::read_only)) {
					throw new \RuntimeException('Runtime observation requires one fixed readonly input and a value return');
				}
			}
			$pattern = self::type($input->type, $types, $positions);
			if ($pattern instanceof runtime_concrete_pattern) {
				$concrete = object_cast($pattern, runtime_concrete_pattern::class);
				if ($types->registry()->type($concrete->type->type_id()) instanceof no_value_type) {
					throw new \RuntimeException('Void runtime parameter');
				}
			}
			$parameter = new runtime_pattern_parameter($pattern, $input->mode, $input->access);
			$parameter->variadic = $input->variadic;
			if ($input->default_value !== null) {
				if ($input->mode !== passing_mode::value) { throw new \RuntimeException('Runtime defaults require value parameters'); }
				$default_spec /** runtime_default_spec */ = $input->default_value;
				$parameter->default_value = Runtime_Default_Registration::resolve($default_spec, $types);
				if ($pattern instanceof runtime_concrete_pattern) {
					Runtime_Default_Registration::require_type($parameter->default_value, object_cast($pattern, runtime_concrete_pattern::class)->type);
				}
				elseif (!($pattern instanceof runtime_variable_pattern)) {
					throw new \RuntimeException('Runtime scalar default cannot initialize a type application');
				}
				$optional_seen = true;
			}
			elseif ($optional_seen && !$input->variadic) { throw new \RuntimeException('Runtime required parameter cannot follow an optional parameter'); }
			$parameters->append($parameter);
		}
		foreach ($positions as $position) {
			if ($position < $owner_count) { continue; }
			$found = false;
			foreach ($parameters as $parameter) {
				if (self::contains($parameter->type, $position)) { $found = true; }
			}
			if (!$found) { throw new \RuntimeException('Runtime type parameter must be inferable from an input'); }
		}
		$result->return_type = self::type($spec->return_type, $types, $positions);
		return $result;
	}

	/** Resolve explicit variables, closed types and application structure without dummy types. */
	public static function type(runtime_type_spec $spec, registered_type_catalog $types,
		array $positions /** hash<int> */): runtime_type_pattern
	{
		if ($spec->parameter !== '') {
			if (!isset($positions[$spec->parameter])) { throw new \RuntimeException('Unknown runtime type parameter: ' . $spec->parameter); }
			return new runtime_variable_pattern($positions[$spec->parameter]);
		}
		$definition = $types->definition($spec->name);
		if (!($definition instanceof abstract_template_type_definition)) {
			return new runtime_concrete_pattern(Runtime_Catalog_Registration::type($spec, $types));
		}
		$template_definition = object_cast($definition, abstract_template_type_definition::class);
		$count = q_count($spec->arguments);
		if (($count < $template_definition->required_arity()) || ($count > $template_definition->arity())) {
			throw new \RuntimeException('Invalid runtime type pattern arity');
		}
		$result = new runtime_application_pattern($template_definition);
		$arguments /** Storage<runtime_type_pattern> */ = $result->arguments;
		$closed /** vector<canonical_type_use> */ = [];
		foreach ($spec->arguments as $argument) {
			$child = self::type($argument, $types, $positions);
			$arguments->append($child);
			if ($child instanceof runtime_concrete_pattern) {
				$closed[] = object_cast($child, runtime_concrete_pattern::class)->type;
			}
		}
		for ($index = $count; $index < $template_definition->arity(); $index++) {
			$default_type = $template_definition->parameter($index)->default_type();
			if ($default_type === null) { throw new \LogicException('Missing registered template default'); }
			$arguments->append(new runtime_concrete_pattern($default_type));
			$closed[] = $default_type;
		}
		if (q_count($closed) === q_count($arguments)) {
			$registry = $types->registry();
			$application = $registry->intern_application($template_definition, $closed);
			return new runtime_concrete_pattern($registry->use($application->type_id()));
		}
		return $result;
	}

	private static function contains(runtime_type_pattern $pattern, int $position): bool
	{
		if ($pattern instanceof runtime_variable_pattern) {
			return object_cast($pattern, runtime_variable_pattern::class)->position === $position;
		}
		if ($pattern instanceof runtime_application_pattern) {
			$application = object_cast($pattern, runtime_application_pattern::class);
			foreach ($application->arguments as $child) {
				if (self::contains($child, $position)) { return true; }
			}
		}
		return false;
	}

	/** Alpha-equivalent recipes collide regardless of authored variable names. */
	public static function key(runtime_type_pattern $pattern): string
	{
		if ($pattern instanceof runtime_variable_pattern) {
			return '$' . object_cast($pattern, runtime_variable_pattern::class)->position;
		}
		if ($pattern instanceof runtime_concrete_pattern) {
			$type = object_cast($pattern, runtime_concrete_pattern::class)->type;
			return 't' . $type->type_id() . ($type->by_value() ? 'v' : '');
		}
		$application = object_cast($pattern, runtime_application_pattern::class);
		$result = 'a' . $application->definition->definition_id() . '<';
		foreach ($application->arguments as $child) { $result .= self::key($child) . ','; }
		return $result . '>';
	}
}
