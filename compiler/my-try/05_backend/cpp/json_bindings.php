<?php

/* Backend installation follows semantic publication; no source spelling switches at call sites. */
namespace scpp\compiler;

final class CPP_JSON_Registration
{
	/** Bind installed provider identities to the runtime's out-of-line JSON API. */
	public static function install(): void
	{
		foreach (['mixed', 'dynamic'] as $family) {
			$decode = Model::$runtime_callables->function_definition('json.decode.' . $family);
			$encode = Model::$runtime_callables->function_definition('json.encode.' . $family);
			$symbol = $family === 'dynamic' ? 'scpp::json::decode' : 'scpp::json::decode_mixed';
			Model::$cpp_callable_bindings->bind_function($decode, $symbol, 'modules/json/json.hpp');
			Model::$cpp_callable_bindings->bind_function($encode, 'scpp::json::encode', 'modules/json/json.hpp');
		}
	}
}
