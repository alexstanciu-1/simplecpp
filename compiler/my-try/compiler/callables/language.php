<?php
namespace scpp\compiler;

final class Language_Callables
{
	public static function install(scope $language): void
	{
		$language->register_compiler_function(new compiler_function_definition('enum_name', compiler_function_kind::enum_name));
	}
}
