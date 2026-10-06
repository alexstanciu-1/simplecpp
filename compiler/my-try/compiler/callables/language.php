<?php
namespace scpp\compiler;

final class Language_Callables
{
	public static function install(scope $language): void
	{
		$language->register_compiler_function(new compiler_function_definition('enum_name', compiler_function_kind::enum_name));
		$language->register_compiler_function(new compiler_function_definition('enum_value', compiler_function_kind::enum_value));
		$language->register_compiler_function(new compiler_function_definition('enum_from_value', compiler_function_kind::enum_from_value));
	}
}
