<?php

/* Installed source API contracts only; JSON execution and C++ spelling have separate owners. */
namespace scpp\compiler;

final class JSON_Callable_Registration
{
	/** Declare both family contracts without adding JSON knowledge to lookup or preparation. */
	public static function install(scope $language): void
	{
		$catalog = Model::$type_catalog;
		$registry = $catalog->registry();
		$string = $registry->use($catalog->canonical('string')->type_id());
		foreach (['mixed', 'dynamic'] as $family)
		{
			$payload = $registry->use($catalog->canonical($family)->type_id());
			$decode_name = $family === 'dynamic' ? 'json_decode' : 'json_decode_mixed';
			self::register($language, 'json.decode.' . $family, $decode_name, $string, self::result_type($payload));
			self::register($language, 'json.encode.' . $family, 'json_encode', $payload, self::result_type($string));
		}
	}

	private static function result_type(canonical_type_use $payload): canonical_type_use
	{
		$registry = Model::$type_catalog->registry();
		$arguments /** vector<canonical_type_use> */ = [$payload];
		$definition = object_cast(Model::$type_catalog->definition('result'), abstract_template_type_definition::class);
		$application = $registry->intern_application($definition, $arguments);
		return $registry->use($application->type_id());
	}

	/** Share the catalog-owned signature and target with the language scope index. */
	private static function register(scope $language, string $key, string $name,
		canonical_type_use $input, canonical_type_use $output): void
	{
		$signature = new callable_signature();
		$signature->return_type = $output;
		$parameter = new callable_parameter();
		$parameter->type = $input;
		$parameter->mode = passing_mode::value;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$parameters->append($parameter);
		$language->register_function(Model::$runtime_callables->register_function($key, $name, $signature));
	}
}
