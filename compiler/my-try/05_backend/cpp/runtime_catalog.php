<?php
namespace scpp\compiler;

/** Backend-only consumption of independent provider bindings. */
final class CPP_Runtime_Catalog
{
	/** Catalogs are trusted metadata, but bindings are names/paths, never C++ expressions. */
	public static function validate(runtime_catalog_input $input): void
	{
		foreach ($input->modules as $module)
		{
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
			}
			foreach ($module->cpp as $binding) {
				self::spelling($binding->symbol, false);
				self::spelling($binding->header, true);
			}
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
		foreach ($input->modules as $module) {
			foreach ($module->cpp as $binding) {
				$target = Model::$runtime_callables->function_definition($binding->identity);
				Model::$cpp_callable_bindings->bind_function($target, $binding->symbol, $binding->header);
			}
		}
	}
}
