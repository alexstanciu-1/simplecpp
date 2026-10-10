<?php
namespace scpp\compiler;

/** Shared type materialization for runtime callable and property recipes. */
final class Runtime_Pattern_Preparation
{
	public static function owner_arguments(canonical_type_use $owner): runtime_type_bindings
	{
		$result = new runtime_type_bindings();
		$type = Type_Queries::canonical($owner);
		if ($type instanceof applied_template_type) {
			$application = object_cast($type, applied_template_type::class);
			foreach ($application->arguments() as $index => $argument) {
				$result->bindings[$index] = $argument;
			}
		}
		return $result;
	}

	/** Preparation owns substitution and dependencies; the registry retains storage identity. */
	public static function property(canonical_type_use $owner, string $name,
		preparation_context $context): ?registered_property
	{
		$definition = Model::$runtime_members->property($owner, $name);
		if ($definition === null) { return null; }
		$type = self::substitute($definition->type_pattern, self::owner_arguments($owner), $context);
		Type_Preparation::require_value_type($type);
		$query = new Capability_Preparation($context);
		if (!$query->has_capability($type, generic_contract::value_storable)) {
			throw new \RuntimeException('Runtime property requires a storable type');
		}
		$property = Model::$runtime_members->stored_property($owner, $definition);
		if ($property !== null) { return $property; }
		$result = new registered_property($definition, $owner);
		$result->type = $type;
		$result->static_member = $definition->static_member;
		$result->access = $definition->access;
		Model::$runtime_members->retain_property($result);
		return $result;
	}

	/** Materialize selected types using the ordinary registry's capability checks. */
	public static function substitute(runtime_type_pattern $pattern, runtime_type_bindings $inference,
		preparation_context $context): canonical_type_use
	{
		if ($pattern instanceof runtime_concrete_pattern) {
			return object_cast($pattern, runtime_concrete_pattern::class)->type;
		}
		if ($pattern instanceof runtime_variable_pattern) {
			return $inference->bindings[object_cast($pattern, runtime_variable_pattern::class)->position];
		}
		$application = object_cast($pattern, runtime_application_pattern::class);
		$arguments /** vector<canonical_type_use> */ = [];
		foreach ($application->arguments as $child) { $arguments[] = self::substitute($child, $inference, $context); }
		$registry = Model::$type_catalog->registry();
		try {
			$type = $registry->intern_application($application->definition, $arguments, new Capability_Preparation($context));
			return $registry->use($type->type_id());
		}
		catch (\InvalidArgumentException $error) { throw new \RuntimeException($error->getMessage()); }
	}
}
