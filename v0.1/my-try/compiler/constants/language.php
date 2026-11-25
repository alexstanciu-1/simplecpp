<?php

/* Built-in constants follow the fixed Simple C++ language model, not the host PHP runtime. */
namespace scpp\compiler;

final class Language_Constants
{
	/** Install the bounded constant surface after canonical language types exist. */
	public static function install(scope $language_scope, registered_type_catalog $types): void
	{
		$maximum = new integer_constant_value();
		$maximum->type = $types->registry()->use($types->canonical('int')->type_id());
		$maximum->decimal = '9223372036854775807';
		$language_scope->register_constant(new registered_constant('PHP_INT_MAX', $maximum));
		$no_error = new no_error_constant_value();
		$no_error->type = $types->registry()->use($types->canonical('error')->type_id());
		$language_scope->register_constant(new registered_constant('error::$no_error', $no_error));

		$markers /** hash<canonical_type_use> */ = [
			'false_sentinel' => $types->boolean_sentinel_type(false),
			'true_sentinel' => $types->boolean_sentinel_type(true),
			'nullopt' => $types->null_type(),
		];
		foreach ($markers as $name => $type)
		{
			$marker = new sentinel_constant_value();
			$marker->type = $type;
			$language_scope->register_constant(new registered_constant($name, $marker));
		}
	}
}
