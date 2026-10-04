<?php

/* Built-in constants follow the fixed Simple C++ language model, not the host PHP runtime. */
namespace scpp\compiler;

final class Language_Constants
{
	/** Install the bounded constant surface after canonical language types exist. */
	public static function install(scope $language_scope): void
	{
		$maximum = new integer_constant_definition();
		$maximum->name = 'PHP_INT_MAX';
		$maximum->type = Language_Types::integer($language_scope);
		$maximum->decimal = '9223372036854775807';
		$language_scope->register_constant($maximum);
		$no_error = new no_error_constant_definition();
		$no_error->name = 'error::$no_error';
		$no_error->type = Language_Types::error_type($language_scope);
		$language_scope->register_constant($no_error);

		$markers /** hash<canonical_type_use> */ = [
			'false_sentinel' => Model::$type_catalog->boolean_sentinel_type(false),
			'true_sentinel' => Model::$type_catalog->boolean_sentinel_type(true),
			'nullopt' => Model::$type_catalog->null_type(),
		];
		foreach ($markers as $name => $type)
		{
			$marker = new sentinel_constant_definition();
			$marker->name = $name;
			$marker->type = $type;
			$language_scope->register_constant($marker);
		}
	}
}
