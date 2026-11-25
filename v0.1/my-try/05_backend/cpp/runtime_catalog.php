<?php
namespace scpp\compiler;

/** Backend-only consumption of independent provider bindings. */
final class CPP_Runtime_Catalog
{
	/** Catalogs are trusted metadata, but bindings are names/paths, never C++ expressions. */
	public static function validate(runtime_catalog_input $input, registered_type_catalog $types): void
	{
		CPP_Runtime_Build::requirements($input);
		CPP_Runtime_Type_Bindings::install($input, $types, CPP_Type_Binding_Registration::install($types));
		foreach ($input->modules as $module)
		{
			$initializers /** Keyed_Storage<runtime_cpp_spec> */ = $module->cpp_initialization;
			$initializer_count = 0;
			foreach ($module->types as $owner_spec) {
				$initializations /** Storage<runtime_initialization_spec> */ = $owner_spec->initialization;
				if ($initializations->is_empty()) { continue; }
				$initializer_count++;
				if (!isset($initializers[$owner_spec->identity])) {
					throw new \RuntimeException('Missing runtime initialization binding');
				}
			}
			if ($initializer_count !== q_count($initializers)) {
				throw new \RuntimeException('Runtime initialization binding count mismatch');
			}
			foreach ($initializers as $initializer) {
				self::spelling($initializer->symbol, false);
				self::spelling($initializer->header, true);
			}
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
				$property_owner = $types->definition($property->owner->name);
				if (!$property->static_member || ($property_owner instanceof abstract_template_type_definition)) {
					self::member_identifier($property_binding->symbol);
				}
			}
			$bindings /** Keyed_Storage<runtime_cpp_spec> */ = $module->cpp;
			if (q_count($bindings) !== q_count($module->functions)) {
				throw new \RuntimeException('Runtime binding count mismatch: ' . $module->file);
			}
			foreach ($module->functions as $function) {
				if (!isset($bindings[$function->identity])) {
					throw new \RuntimeException('Missing runtime binding: ' . $function->identity);
				}
				if ($bindings[$function->identity]->native_payloads) {
					if ($function->member !== null) { throw new \RuntimeException('Native payload policy requires a free function'); }
					foreach ($function->parameters as $parameter) {
						if (($parameter->mode !== passing_mode::value) || $parameter->variadic) {
							throw new \RuntimeException('Native payload policy requires fixed value parameters');
						}
					}
				}
				if ($bindings[$function->identity]->source_context) {
					$parameters /** Storage<runtime_parameter_spec> */ = $function->parameters;
					if (($function->member !== null) || $parameters->is_empty()) { throw new \RuntimeException('Call-site context requires a free function with value inputs'); }
					foreach ($function->parameters as $parameter) {
						if (($parameter->mode !== passing_mode::value) || $parameter->variadic) {
							throw new \RuntimeException('Call-site context requires fixed value parameters');
						}
					}
				}
				if ($function->member !== null) {
					$member /** runtime_callable_member_spec */ = $function->member;
					$owner = $types->definition($member->owner->name);
					if (!$member->static_member || ($owner instanceof abstract_template_type_definition)) {
						$symbol = $bindings[$function->identity]->symbol;
						if (q_strpos($symbol, ':') !== false) {
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
		if ((q_strpos($symbol, ':') !== false) || ($symbol === '')) {
			throw new \RuntimeException('Runtime property binding requires an unqualified identifier');
		}
		$first = string_byte_at($symbol, 0);
		if (($first >= 48) && ($first <= 57)) {
			throw new \RuntimeException('Runtime property binding requires an identifier');
		}
	}

	/** Reject expression/source fragments; the C++ toolchain still proves symbol existence. */
	public static function spelling(string $text, bool $header): void
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
		CPP_Runtime_Type_Bindings::install($input, $types, $bindings);
	}

	/** Callable installation follows both semantic and type-binding installation. */
	public static function install(runtime_catalog_input $input): void
	{
		$properties /** hash<runtime_cpp_spec, runtime_property_definition> */ = new \SplObjectStorage /** hash<runtime_cpp_spec, runtime_property_definition> */();
		Model::$cpp_property_bindings = $properties;
		// Installation callees do not observe this map; publish partial state on failure too.
		try {
			foreach ($input->modules as $module) {
				foreach ($module->cpp_properties as $property_binding) {
					$property = Model::$runtime_members->property_identity($property_binding->identity);
					$properties[$property] = $property_binding;
				}
				foreach ($module->cpp as $binding) {
					$target = Model::$runtime_callables->function_definition($binding->identity);
					Model::$cpp_callable_bindings->bind_function($target, $binding->symbol, $binding->header, $binding->source_context, $binding->native_payloads);
				}
			}
		}
		finally { Model::$cpp_property_bindings = $properties; }
	}

	public static function property(registered_property $property, cpp_generation_context $context): string
	{
		$definition = $property->definition;
		if (!isset(Model::$cpp_property_bindings[$definition])) {
			throw new \LogicException('Missing or stale runtime property binding');
		}
		$binding /** runtime_cpp_spec */ = Model::$cpp_property_bindings[$definition];
		$context->headers[$binding->header] = true;
		if ($definition->static_member && ($definition->owner instanceof abstract_template_type_definition)) {
			$mapping = CPP_Types::payload($property->owner_type, $context);
			CPP_Types::record_dependencies($mapping, $context);
			return $mapping->spelling . '::' . $binding->symbol;
		}
		return $binding->symbol;
	}
}
