<?php
namespace scpp\compiler;

/** Backend-only consumption of independent provider bindings. */
final class CPP_Runtime_Catalog
{
	/** Catalogs are trusted metadata, but bindings are names/paths, never C++ expressions. */
	public static function validate(runtime_catalog_input $input, registered_type_catalog $types): void
	{
		foreach ($input->modules as $module)
		{
			$properties /** Keyed_Storage<runtime_cpp_spec> */ = $module->cpp_properties;
			if (q_count($properties) !== q_count($module->properties)) {
				throw new \RuntimeException('Runtime property binding count mismatch');
			}
			foreach ($module->properties as $property)
			{
				if (!isset($properties[$property->identity])) { throw new \RuntimeException('Missing runtime property binding'); }
				$property_binding = $properties[$property->identity];
				self::spelling($property_binding->symbol, false);
				self::spelling($property_binding->header, true);
				if (!$property->static_member) { self::member_identifier($property_binding->symbol); }
			}
			$type_bindings /** Keyed_Storage<runtime_cpp_spec> */ = $module->cpp_types;
			if (q_count($type_bindings) !== q_count($module->types)) {
				throw new \RuntimeException('Runtime type binding count mismatch: ' . $module->file);
			}
			foreach ($module->types as $type_spec) {
				if (!isset($type_bindings[$type_spec->identity])) {
					throw new \RuntimeException('Missing runtime type binding: ' . $type_spec->identity);
				}
			}
			foreach ($type_bindings as $type_binding) {
				self::spelling($type_binding->symbol, false);
				self::spelling($type_binding->header, true);
			}
			$bindings /** Keyed_Storage<runtime_cpp_spec> */ = $module->cpp;
			if (q_count($bindings) !== q_count($module->functions)) {
				throw new \RuntimeException('Runtime binding count mismatch: ' . $module->file);
			}
			foreach ($module->functions as $function) {
				if (!isset($bindings[$function->identity])) {
					throw new \RuntimeException('Missing runtime binding: ' . $function->identity);
				}
				if ($function->member !== null) {
					$member /** runtime_callable_member_spec */ = $function->member;
					$owner = $types->definition($member->owner->name);
					if (!$member->static_member || ($owner instanceof abstract_template_type_definition)) {
						$symbol = $bindings[$function->identity]->symbol;
						if (str_contains($symbol, ':')) {
							throw new \RuntimeException('Runtime instance binding requires an unqualified member name');
						}
						if (string_byte_len($symbol) === 0) {
							throw new \RuntimeException('Empty runtime instance member binding');
						}
						$first = string_byte_at($symbol, 0);
						if (($first >= 48) && ($first <= 57)) {
							throw new \RuntimeException('Runtime instance binding requires an identifier');
						}
					}
				}
			}
			foreach ($module->cpp as $binding) {
				self::spelling($binding->symbol, false);
				self::spelling($binding->header, true);
			}
		}
	}

	private static function member_identifier(string $symbol): void
	{
		if (str_contains($symbol, ':') || ($symbol === '')) {
			throw new \RuntimeException('Runtime property binding requires an unqualified identifier');
		}
		$first = string_byte_at($symbol, 0);
		if (($first >= 48) && ($first <= 57)) {
			throw new \RuntimeException('Runtime property binding requires an identifier');
		}
	}

	/** Reject expression/source fragments; the C++ toolchain still proves symbol existence. */
	private static function spelling(string $text, bool $header): void
	{
		if ($text === '') {
			throw new \RuntimeException('Empty runtime C++ binding');
		}
		for ($index = 0; $index < string_byte_len($text); $index++)
		{
			$byte = string_byte_at($text, $index);
			if ((($byte >= 65) && ($byte <= 90)) || (($byte >= 97) && ($byte <= 122))
				|| (($byte >= 48) && ($byte <= 57)) || ($byte === 95)) {
				continue;
			}
			if ($header) {
				if (($byte === 47) || ($byte === 46) || ($byte === 45)) {
					continue;
				}
			}
			elseif ($byte === 58) {
				continue;
			}
			throw new \RuntimeException('Invalid runtime C++ binding spelling: ' . $text);
		}
	}

	/** Bind current identities after semantic installation, never during schema decoding. */
	public static function install_types(runtime_catalog_input $input, registered_type_catalog $types,
		CPP_Type_Bindings $bindings): void
	{
		foreach ($input->modules as $module)
		{
			$specs /** Keyed_Storage<runtime_cpp_spec> */ = $module->cpp_types;
			foreach ($module->types as $type_spec) {
				$binding = $specs[$type_spec->identity];
				$bindings->bind_definition($types->definition($type_spec->name), $binding->symbol, $binding->header);
			}
		}
	}

	/** Callable installation follows both semantic and type-binding installation. */
	public static function install(runtime_catalog_input $input): void
	{
		Model::$cpp_property_bindings = new \SplObjectStorage();
		foreach ($input->modules as $module) {
			foreach ($module->cpp_properties as $property_binding) {
				$property = Model::$runtime_members->property_identity($property_binding->identity);
				Model::$cpp_property_bindings[$property] = $property_binding;
			}
			foreach ($module->cpp as $binding) {
				$target = Model::$runtime_callables->function_definition($binding->identity);
				Model::$cpp_callable_bindings->bind_function($target, $binding->symbol, $binding->header);
			}
		}
	}

	public static function property(registered_property $property, cpp_generation_context $context): string
	{
		if (!isset(Model::$cpp_property_bindings[$property])) {
			throw new \LogicException('Missing or stale runtime property binding');
		}
		$binding /** runtime_cpp_spec */ = Model::$cpp_property_bindings[$property];
		$context->headers[$binding->header] = true;
		return $binding->symbol;
	}
}
