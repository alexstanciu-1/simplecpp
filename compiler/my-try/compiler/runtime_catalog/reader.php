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
			elseif ($key === 'constants')
			{
				$constants /** Storage<runtime_constant_spec> */ = $result->constants;
				$reader->begin_array();
				while ($reader->next_element()) {
					$constants->append(self::constant_spec($reader));
				}
			}
			elseif ($key === 'types')
			{
				$types /** Storage<runtime_type_definition_spec> */ = $result->types;
				$reader->begin_array();
				while ($reader->next_element()) {
					$types->append(self::definition_spec($reader));
				}
			}
			elseif ($key === 'properties')
			{
				$properties /** Storage<runtime_property_spec> */ = $result->properties;
				$reader->begin_array();
				while ($reader->next_element()) {
					$properties->append(self::property_spec($reader));
				}
			}
			elseif ($key === 'cpp_build') { $result->cpp_build = self::build_spec($reader); }
			elseif (($key === 'cpp') || ($key === 'cpp_types') || ($key === 'cpp_properties') || ($key === 'cpp_initialization'))
			{
				$bindings /** Keyed_Storage<runtime_cpp_spec> */ = $result->cpp;
				if ($key === 'cpp_types') {
					$bindings = $result->cpp_types;
				}
				elseif ($key === 'cpp_properties') { $bindings = $result->cpp_properties; }
				elseif ($key === 'cpp_initialization') { $bindings = $result->cpp_initialization; }
				$reader->begin_array();
				while ($reader->next_element()) {
					$binding = self::cpp_spec($reader);
					if (isset($bindings[$binding->identity])) {
						$reader->reject('Duplicate C++ binding identity');
					}
					$bindings->add($binding->identity, $binding);
				}
				if ($key === 'cpp') {
					$cpp = true;
				}
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

	private static function build_spec(\scpp\Json_Reader $reader): runtime_cpp_build_spec
	{
		$result = new runtime_cpp_build_spec();
		$reader->begin_object();
		while ($reader->next_property()) {
			$name = $reader->property_name();
			if (($name !== 'sources') && ($name !== 'libraries') && ($name !== 'definitions')) {
				$reader->reject('Unknown runtime build field: ' . $name);
			}
			$values /** vector<string> */ = [];
			$reader->begin_array();
			while ($reader->next_element()) { $values[] = self::name($reader); }
			if ($name === 'sources') { $result->sources = $values; }
			elseif ($name === 'libraries') { $result->libraries = $values; }
			else { $result->definitions = $values; }
		}
		Runtime_Build_Contract::validate($result);
		return $result;
	}

	private static function property_spec(\scpp\Json_Reader $reader): runtime_property_spec
	{
		$result = new runtime_property_spec();
		$owner = false;
		$type = false;
		$reader->begin_object();
		while ($reader->next_property())
		{
			$key = $reader->property_name();
			if ($key === 'id') { $result->identity = self::name($reader); }
			elseif ($key === 'name') { $result->name = self::name($reader); }
			elseif ($key === 'owner') { $result->owner = self::type($reader); $owner = true; }
			elseif ($key === 'type') { $result->type = self::type($reader); $type = true; }
			elseif ($key === 'static') { $result->static_member = $reader->read_boolean(); }
			elseif ($key === 'access') {
				$access = $reader->read_string();
				if ($access === 'read_only') { $result->access = storage_access::read_only; }
				elseif ($access !== 'writable') { $reader->reject('Unknown runtime property access'); }
			}
			else { $reader->reject('Unknown runtime property field: ' . $key); }
		}
		if (!$owner || !$type || ($result->identity === '') || ($result->name === '')) {
			$reader->reject('Missing runtime property field');
		}
		return $result;
	}

	/** Closed vocabulary mirrors the registry's named contracts; no executable rules. */
	private static function capabilities(\scpp\Json_Reader $reader): array /** vector<generic_contract> */
	{
		$result /** vector<generic_contract> */ = [];
		$seen /** hash<bool> */ = [];
		$reader->begin_array();
		while ($reader->next_element())
		{
			$name = self::name($reader);
			if (isset($seen[$name])) {
				$reader->reject('Duplicate runtime capability: ' . $name);
			}
			$seen[$name] = true;
			if ($name === 'copyable_value') {
				$result[] = generic_contract::copyable_value;
			}
			elseif ($name === 'value_storable') {
				$result[] = generic_contract::value_storable;
			}
			elseif ($name === 'hashable') {
				$result[] = generic_contract::hashable;
			}
			elseif ($name === 'comparable') {
				$result[] = generic_contract::comparable;
			}
			elseif ($name === 'result_payload') {
				$result[] = generic_contract::result_payload;
			}
			else {
				$reader->reject('Unknown runtime capability: ' . $name);
			}
		}
		return $result;
	}

	/** Decode explicit formation requirements separately from conditional capabilities. */
	private static function type_parameter_spec(\scpp\Json_Reader $reader): runtime_type_parameter_spec
	{
		$result = new runtime_type_parameter_spec();
		$has_requirements = false;
		$reader->begin_object();
		while ($reader->next_property())
		{
			$key = $reader->property_name();
			if ($key === 'name') {
				$result->name = self::name($reader);
			}
			elseif ($key === 'requires') {
				$result->formation_contracts = self::capabilities($reader);
				$has_requirements = true;
			}
			elseif ($key === 'capability_dependencies') {
				$result->capability_dependencies = self::capabilities($reader);
			}
			elseif ($key === 'default') {
				$result->default_type = self::type($reader);
			}
			else {
				$reader->reject('Unknown runtime type parameter field: ' . $key);
			}
		}
		if (($result->name === '') || !$has_requirements) {
			$reader->reject('Missing runtime type parameter field');
		}
		return $result;
	}

	/** Ordinary type declarations have no language-role or backend interpretation here. */
	private static function definition_spec(\scpp\Json_Reader $reader): runtime_type_definition_spec
	{
		$result = new runtime_type_definition_spec();
		$kind = false;
		$capabilities = false;
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
			elseif ($key === 'kind')
			{
				$spelling = self::name($reader);
				if ($spelling === 'class') {
					$result->kind = nominal_type_kind::class_type;
				}
				elseif ($spelling === 'struct') {
					$result->kind = nominal_type_kind::structure;
				}
				else {
					$reader->reject('Unsupported runtime type kind: ' . $spelling);
				}
				$kind = true;
			}
			elseif ($key === 'storage')
			{
				$storage_name = self::name($reader);
				if ($storage_name === 'inline') {
					$result->storage = record_storage_kind::inline_value;
				}
				elseif ($storage_name === 'shared') {
					$result->storage = record_storage_kind::shared;
				}
				else {
					$reader->reject('Unsupported runtime storage: ' . $storage_name);
				}
			}
			elseif ($key === 'capabilities') {
				$result->capabilities = self::capabilities($reader);
				$capabilities = true;
			}
			elseif ($key === 'parameters') {
				$parameters /** Storage<runtime_type_parameter_spec> */ = $result->parameters;
				$reader->begin_array();
				while ($reader->next_element()) {
					$parameters->append(self::type_parameter_spec($reader));
				}
			}
			elseif ($key === 'indexing') { $result->indexing = self::indexing_spec($reader); }
			elseif ($key === 'iteration') { $result->iteration = self::iteration_spec($reader); }
			elseif ($key === 'initialization') {
				$recipes /** Storage<runtime_initialization_spec> */ = $result->initialization;
				$reader->begin_array();
				while ($reader->next_element()) { $recipes->append(self::initialization_spec($reader)); }
			}
			else {
				$reader->reject('Unknown runtime type definition field: ' . $key);
			}
		}
		if (($result->identity === '') || ($result->name === '') || !$kind || !$capabilities) {
			$reader->reject('Missing runtime type definition field');
		}
		return $result;
	}

	private static function initialization_spec(\scpp\Json_Reader $reader): runtime_initialization_spec
	{
		$result = new runtime_initialization_spec();
		$value_seen = false;
		$nested_seen = false;
		$reader->begin_object();
		while ($reader->next_property()) {
			$name = $reader->property_name();
			if ($name === 'value') { $result->value = self::type($reader); $value_seen = true; }
			elseif ($name === 'key') { $result->key = self::type($reader); }
			elseif ($name === 'nested') { $result->nested = self::type($reader); $nested_seen = true; }
			elseif ($name === 'where') {
				$constraints /** Keyed_Storage<runtime_type_spec> */ = $result->where;
				$reader->begin_object();
				while ($reader->next_property()) {
					$parameter = $reader->property_name();
					$constraints->add($parameter, self::type($reader));
				}
			}
			else { $reader->reject('Unknown runtime initialization field: ' . $name); }
		}
		if (!$value_seen || !$nested_seen) { $reader->reject('Missing runtime initialization field'); }
		return $result;
	}

	private static function iteration_spec(\scpp\Json_Reader $reader): runtime_iteration_spec
	{
		$result = new runtime_iteration_spec();
		$key_seen = false;
		$value_seen = false;
		$reader->begin_object();
		while ($reader->next_property()) {
			$name = $reader->property_name();
			if ($name === 'key') { $result->key = self::type($reader); $key_seen = true; }
			elseif ($name === 'value') { $result->value = self::type($reader); $value_seen = true; }
			else { $reader->reject('Unknown runtime iteration field: ' . $name); }
		}
		if (!$key_seen || !$value_seen) { $reader->reject('Missing runtime iteration field'); }
		return $result;
	}

	private static function indexing_spec(\scpp\Json_Reader $reader): runtime_indexing_spec
	{
		$result = new runtime_indexing_spec();
		$key_seen = false;
		$value_seen = false;
		$nested_seen = false;
		$reader->begin_object();
		while ($reader->next_property()) {
			$name = $reader->property_name();
			if ($name === 'key') { $result->key = self::type($reader); $key_seen = true; }
			elseif ($name === 'value') { $result->value = self::type($reader); $value_seen = true; }
			elseif ($name === 'nested') { $result->nested = self::type($reader); $nested_seen = true; }
			elseif ($name === 'path') { $result->path = self::type($reader); }
			elseif ($name === 'write') { $result->write = $reader->read_boolean(); }
			elseif ($name === 'probes') { $result->probes = $reader->read_boolean(); }
			elseif ($name === 'removal') { $result->removal = $reader->read_boolean(); }
			elseif ($name === 'read') {
				$read = self::name($reader);
				if ($read === 'null_on_miss') { $result->read_operation = operator_operation::index_null_read; }
				elseif ($read !== 'checked') { $reader->reject('Unknown runtime indexing read policy'); }
			}
			elseif ($name === 'append') {
				$append = self::name($reader);
				if ($append === 'allowed') { $result->append = index_append_policy::allowed; }
				elseif ($append === 'integer_key') { $result->append = index_append_policy::integer_key; }
				elseif ($append !== 'unsupported') { $reader->reject('Unknown runtime indexing append policy'); }
			}
			else { $reader->reject('Unknown runtime indexing field: ' . $name); }
		}
		if (!$key_seen || !$value_seen || !$nested_seen) { $reader->reject('Missing runtime indexing field'); }
		if (!$result->write && ($result->append !== index_append_policy::unsupported)) {
			$reader->reject('Runtime append requires indexed write support');
		}
		return $result;
	}

	/** The reader checks signed-64 range before conversion; no floating-point round trip. */
	private static function constant_spec(\scpp\Json_Reader $reader): runtime_constant_spec
	{
		$result = new runtime_constant_spec();
		$reader->begin_object();
		while ($reader->next_property()) {
			$key = $reader->property_name();
			if ($key === 'name') { $result->name = self::name($reader); }
			elseif ($key === 'type') { $result->type = self::name($reader); }
			elseif ($key === 'value') { $result->decimal = (string)$reader->read_integer(); }
			elseif ($key === 'owner') { $result->owner = self::type($reader); }
			else { $reader->reject('Unknown runtime constant field: ' . $key); }
		}
		if (($result->name === '') || ($result->type === '') || ($result->decimal === '')) {
			$reader->reject('Missing runtime constant field');
		}
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
			elseif ($key === 'callable') { $result->callable = self::callable_spec($reader); }
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
		if ($result->callable !== null) {
			if (($result->name !== '') || ($result->parameter !== '') || $arguments) {
				$reader->reject('Callable type cannot also name another type form');
			}
			return $result;
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

	private static function callable_spec(\scpp\Json_Reader $reader): runtime_callable_spec
	{
		$result = new runtime_callable_spec();
		$returns = false;
		$parameters = false;
		$reader->begin_object();
		while ($reader->next_property()) {
			$name = $reader->property_name();
			if ($name === 'returns') { $result->return_type = self::type($reader); $returns = true; }
			elseif ($name === 'parameters') {
				$inputs /** Storage<runtime_parameter_spec> */ = $result->parameters;
				$reader->begin_array();
				while ($reader->next_element()) {
					$parameter = self::parameter($reader);
					if (($parameter->default_value !== null) || ($parameter->mode === passing_mode::observation)) {
						$reader->reject('Callable type inputs do not support defaults or observation');
					}
					$inputs->append($parameter);
				}
				$parameters = true;
			}
			else { $reader->reject('Unknown runtime callable type field: ' . $name); }
		}
		if (!$returns || !$parameters) { $reader->reject('Missing runtime callable type field'); }
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
			elseif ($key === 'default') { $result->default_value = self::default_spec($reader); }
			elseif ($key === 'variadic') { $result->variadic = $reader->read_boolean(); }
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

	private static function default_spec(\scpp\Json_Reader $reader): runtime_default_spec
	{
		$result = new runtime_default_spec();
		$reader->begin_object();
		while ($reader->next_property()) {
			if ($result->type_name !== '') { $reader->reject('Runtime default requires one scalar tag'); }
			$result->type_name = $reader->property_name();
			if ($result->type_name === 'int') { $result->text = (string)$reader->read_integer(); }
			elseif ($result->type_name === 'float') { $result->text = $reader->read_number(); }
			elseif ($result->type_name === 'string') { $result->text = $reader->read_string(); }
			elseif ($result->type_name === 'bool') { $result->text = $reader->read_boolean() ? 'true' : 'false'; }
			else { $reader->reject('Unsupported runtime default scalar tag'); }
		}
		if ($result->type_name === '') { $reader->reject('Runtime default requires one scalar tag'); }
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
			elseif ($key === 'member') {
				$result->member = self::callable_member($reader);
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

	/** Decode source-level membership without inventing a source declaration. */
	private static function callable_member(\scpp\Json_Reader $reader): runtime_callable_member_spec
	{
		$result = new runtime_callable_member_spec();
		$owner = false;
		$dispatch = false;
		$reader->begin_object();
		while ($reader->next_property())
		{
			$key = $reader->property_name();
			if ($key === 'owner') {
				$result->owner = self::type($reader);
				$owner = true;
			}
			elseif ($key === 'dispatch') {
				$name = self::name($reader);
				if (($name !== 'static') && ($name !== 'instance')) {
					$reader->reject('Runtime member dispatch requires static or instance');
				}
				$result->static_member = $name === 'static';
				$dispatch = true;
			}
			else {
				$reader->reject('Unknown runtime callable member field: ' . $key);
			}
		}
		if (!$owner || !$dispatch) {
			$reader->reject('Missing runtime callable member field');
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
