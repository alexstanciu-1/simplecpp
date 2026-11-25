<?php
namespace scpp\compiler;

final class Language_Callables
{
	public static function install(scope $language): void
	{
		$language->register_compiler_function(new compiler_function_definition('async_sleep_ms', compiler_function_kind::async_sleep));
		$language->register_compiler_function(new compiler_function_definition('async_wait', compiler_function_kind::async_wait));
		$language->register_compiler_function(new compiler_function_definition('layout_sizeof', compiler_function_kind::layout_sizeof));
		$language->register_compiler_function(new compiler_function_definition('layout_alignof', compiler_function_kind::layout_alignof));
		$language->register_compiler_function(new compiler_function_definition('layout_offsetof', compiler_function_kind::layout_offsetof));
		$language->register_compiler_function(new compiler_function_definition('layout_field_sizeof', compiler_function_kind::layout_field_sizeof));
		$language->register_compiler_function(new compiler_function_definition('enum_name', compiler_function_kind::enum_name));
		$language->register_compiler_function(new compiler_function_definition('enum_value', compiler_function_kind::enum_value));
		$language->register_compiler_function(new compiler_function_definition('enum_from_value', compiler_function_kind::enum_from_value));
	}
}
