<?php
namespace scpp\compiler;

/** Strict schema decoder: JSON tokens -> typed metadata, never live registry writes. */
final class Runtime_Catalog_Reader
{
	/** Publish only a fully consumed, schema-checked module record. */
	public static function decode(string $text, string $file): runtime_module_spec
	{
		$reader = new \scpp\Json_Reader($text, $file, 128);
		$result = new runtime_module_spec();
		$result->file = $file;
		$version = false;
		$dependencies = false;
		$functions = false;
		$cpp = false;
		$reader->begin_object();
		while ($reader->next_property())
		{
			$key = $reader->property_name();
			if ($key === 'version') {
				if ($reader->read_integer() !== 1) {
					$reader->reject('Unsupported runtime catalog version');
				}
				$version = true;
			}
			elseif ($key === 'module') {
				$result->name = self::name($reader);
			}
			elseif ($key === 'dependencies')
			{
				$names /** vector<string> */ = [];
				$reader->begin_array();
				while ($reader->next_element()) {
					$names[] = self::name($reader);
				}
				$result->dependencies = $names;
				$dependencies = true;
			}
			elseif ($key === 'functions')
			{
				$items /** Storage<runtime_function_spec> */ = $result->functions;
				$reader->begin_array();
				while ($reader->next_element()) {
					$items->append(self::function_spec($reader));
				}
				$functions = true;
			}
			elseif ($key === 'cpp')
			{
				$bindings /** Keyed_Storage<runtime_cpp_spec> */ = $result->cpp;
				$reader->begin_array();
				while ($reader->next_element()) {
					$binding = self::cpp_spec($reader);
					if (isset($bindings[$binding->identity])) {
						$reader->reject('Duplicate C++ binding identity');
					}
					$bindings->add($binding->identity, $binding);
				}
				$cpp = true;
			}
			else {
				$reader->reject('Unknown runtime module field: ' . $key);
			}
		}
		if (!$version || !$dependencies || !$functions || !$cpp || ($result->name === '')) {
			$reader->reject('Missing runtime module field');
		}
		$reader->finish_document();
		return $result;
	}

	private static function name(\scpp\Json_Reader $reader): string
	{
		$name = $reader->read_string();
		if ($name === '') {
			$reader->reject('Expected nonempty name');
		}
		return $name;
	}

	/** Type composition remains unresolved until registry installation. */
	private static function type(\scpp\Json_Reader $reader): runtime_type_spec
	{
		$result = new runtime_type_spec();
		if ($reader->kind() === 'string') {
			$result->name = self::name($reader);
			return $result;
		}
		$arguments = false;
		$reader->begin_object();
		while ($reader->next_property())
		{
			$key = $reader->property_name();
			if ($key === 'name') {
				$result->name = self::name($reader);
			}
			elseif ($key === 'parameter') {
				$result->parameter = self::name($reader);
			}
			elseif ($key === 'arguments')
			{
				$items /** Storage<runtime_type_spec> */ = $result->arguments;
				$reader->begin_array();
				while ($reader->next_element()) {
					$items->append(self::type($reader));
				}
				$arguments = true;
			}
			else {
				$reader->reject('Unknown runtime type field: ' . $key);
			}
		}
		if ($result->parameter !== '') {
			if (($result->name !== '') || $arguments) {
				$reader->reject('Type parameter cannot also name a type application');
			}
			return $result;
		}
		if (($result->name === '') || !$arguments) {
			$reader->reject('Missing runtime type field');
		}
		return $result;
	}

	/** Decode explicit passing intent without inventing defaults or inferred types. */
	private static function parameter(\scpp\Json_Reader $reader): runtime_parameter_spec
	{
		$result = new runtime_parameter_spec();
		$typed = false;
		$passing = false;
		$reader->begin_object();
		while ($reader->next_property())
		{
			$key = $reader->property_name();
			if ($key === 'name') {
				$result->name = self::name($reader);
			}
			elseif ($key === 'type') {
				$result->type = self::type($reader);
				$typed = true;
			}
			elseif ($key === 'passing')
			{
				$mode = $reader->read_string();
				if ($mode === 'reference') {
					$result->mode = passing_mode::reference;
				}
				elseif ($mode === 'readonly_reference') {
					$result->mode = passing_mode::reference;
					$result->access = storage_access::read_only;
				}
				elseif ($mode === 'observe') {
					$result->mode = passing_mode::observation;
					$result->access = storage_access::read_only;
				}
				elseif ($mode !== 'value') {
					$reader->reject('Unsupported parameter passing mode');
				}
				$passing = true;
			}
			else {
				$reader->reject('Unknown runtime parameter field: ' . $key);
			}
		}
		if (($result->name === '') || !$typed || !$passing) {
			$reader->reject('Missing runtime parameter field');
		}
		return $result;
	}

	/** Keep provider identity, source spelling and typed contract independent. */
	private static function function_spec(\scpp\Json_Reader $reader): runtime_function_spec
	{
		$result = new runtime_function_spec();
		$returns = false;
		$parameters = false;
		$reader->begin_object();
		while ($reader->next_property())
		{
			$key = $reader->property_name();
			if ($key === 'id') {
				$result->identity = self::name($reader);
			}
			elseif ($key === 'name') {
				$result->name = self::name($reader);
			}
			elseif ($key === 'returns') {
				$result->return_type = self::type($reader);
				$returns = true;
			}
			elseif ($key === 'type_parameters')
			{
				$names /** vector<string> */ = [];
				$reader->begin_array();
				while ($reader->next_element()) {
					$names[] = self::name($reader);
				}
				if ($names === []) { $reader->reject('Generic type parameter list must not be empty'); }
				$result->type_parameters = $names;
			}
			elseif ($key === 'parameters')
			{
				$items /** Storage<runtime_parameter_spec> */ = $result->parameters;
				$reader->begin_array();
				while ($reader->next_element()) {
					$items->append(self::parameter($reader));
				}
				$parameters = true;
			}
			else {
				$reader->reject('Unknown runtime function field: ' . $key);
			}
		}
		if (($result->identity === '') || ($result->name === '') || !$returns || !$parameters) {
			$reader->reject('Missing runtime function field');
		}
		return $result;
	}

	/** Decode backend metadata as data; only the backend interprets its spelling. */
	private static function cpp_spec(\scpp\Json_Reader $reader): runtime_cpp_spec
	{
		$result = new runtime_cpp_spec();
		$reader->begin_object();
		while ($reader->next_property())
		{
			$key = $reader->property_name();
			if ($key === 'id') {
				$result->identity = self::name($reader);
			}
			elseif ($key === 'symbol') {
				$result->symbol = self::name($reader);
			}
			elseif ($key === 'header') {
				$result->header = self::name($reader);
			}
			else {
				$reader->reject('Unknown C++ binding field: ' . $key);
			}
		}
		if (($result->identity === '') || ($result->symbol === '') || ($result->header === '')) {
			$reader->reject('Missing C++ binding field');
		}
		return $result;
	}
}
