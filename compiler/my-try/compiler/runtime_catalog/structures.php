<?php
namespace scpp\compiler;

/** Unresolved metadata owns no canonical identities or stage workers. */
final class runtime_type_spec
{
	public ?runtime_callable_spec $callable = null;
	public bool $void_to_null = false;
	public string $name = '';
	/** Explicit variable spelling distinguishes a type parameter from a registered type. */
	public string $parameter = '';
	public Storage $arguments /** Storage<runtime_type_spec> */;
	public function __construct()
	{
		$this->arguments = new Storage /** Storage<runtime_type_spec> */();
	}
}

/** Ordinary provider definitions reuse registry contracts, not language-role flags. */
final class runtime_type_parameter_spec
{
	public string $name = '';
	public array $formation_contracts /** vector<generic_contract> */ = [];
	public array $capability_dependencies /** vector<generic_contract> */ = [];
	public ?runtime_type_spec $default_type = null;
}

final class runtime_type_definition_spec
{
	public Storage $initialization /** Storage<runtime_initialization_spec> */;
	public ?runtime_indexing_spec $indexing = null;
	public ?runtime_iteration_spec $iteration = null;
	public string $identity = '';
	public string $name = '';
	public nominal_type_kind $kind;
	public record_storage_kind $storage = record_storage_kind::inline_value;
	public array $capabilities /** vector<generic_contract> */ = [];
	public Storage $parameters /** Storage<runtime_type_parameter_spec> */;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<runtime_type_parameter_spec> */();
		$this->initialization = new Storage /** Storage<runtime_initialization_spec> */();
	}
}

final class runtime_initialization_spec
{
	public runtime_type_spec $value;
	public ?runtime_type_spec $key = null;
	public runtime_type_spec $nested;
	public Keyed_Storage $where /** Keyed_Storage<runtime_type_spec> */;
	public function __construct() { $this->where = new Keyed_Storage /** Keyed_Storage<runtime_type_spec> */(); }
}

/** Independent protocol opt-ins; type references use the owning definition's parameters. */
final class runtime_iteration_spec
{
	public runtime_type_spec $key;
	public runtime_type_spec $value;
}

final class runtime_indexing_spec
{
	public runtime_type_spec $key;
	public runtime_type_spec $value;
	public runtime_type_spec $nested;
	public ?runtime_type_spec $path = null;
	public operator_operation $read_operation = operator_operation::index_checked_read;
	public bool $write = false;
	public index_append_policy $append = index_append_policy::unsupported;
	public bool $probes = false;
	public bool $removal = false;
}

final class runtime_parameter_spec
{
	/** Provider copies the input independently of caller-side value transport. */
	public bool $requires_copy = false;
	public bool $requires_transfer = false;
	public bool $variadic = false;
	public ?runtime_default_spec $default_value = null;
	public string $name = '';
	public runtime_type_spec $type;
	public passing_mode $mode = passing_mode::value;
	public storage_access $access = storage_access::writable;
}

/** Explicit scalar tag avoids JSON integer/float ambiguity and host numeric rounding. */
final class runtime_default_spec
{
	public string $type_name = '';
	public string $text = '';
}

abstract class runtime_signature_spec
{
	public runtime_type_spec $return_type;
	public Storage $parameters /** Storage<runtime_parameter_spec> */;
	public function __construct() { $this->parameters = new Storage /** Storage<runtime_parameter_spec> */(); }
}

final class runtime_callable_spec extends runtime_signature_spec {}

/** Named result relation in immutable startup metadata. */
final class runtime_replacement_spec
{
	public string $result_name = '';
	public string $replacement_name = '';
}

/** Scalar or matching boxed-family result requirement in startup metadata. */
final class runtime_boxed_result_spec
{
	public string $result_name = '';
	public string $family_name = '';
}

final class runtime_function_spec extends runtime_signature_spec
{
	/** Provider permits synchronous readonly borrowing in place of value snapshots. */
	public bool $snapshot_borrow_safe = false;
	public ?runtime_callable_member_spec $member = null;
	public string $identity = '';
	public string $name = '';
	public array $qualified_aliases /** vector<string> */ = [];
	public array $type_parameters /** vector<string> */ = [];
	public array $transfer_types /** vector<string> */ = [];
	public Storage $replacement_results /** Storage<runtime_replacement_spec> */;
	public Storage $boxed_results /** Storage<runtime_boxed_result_spec> */;
	public function __construct()
	{
		parent::__construct();
		$this->replacement_results = new Storage /** Storage<runtime_replacement_spec> */();
		$this->boxed_results = new Storage /** Storage<runtime_boxed_result_spec> */();
	}
}

/** Membership is independent of the function signature and backend symbol. */
final class runtime_callable_member_spec
{
	public runtime_type_spec $owner;
	public bool $static_member;
}

/** Backend metadata is separate from type/function semantics, joined only by identity. */
class runtime_cpp_spec
{
	public string $identity = '';
	public string $symbol = '';
	/** Backend-only opt-in to PHS file/line prefix arguments. */
	public bool $source_context = false;
	public bool $native_payloads = false;
	public string $header = '';
}

/** Unresolved C++ type recipe. Absence of arguments retains declaration-order shorthand. */
final class runtime_cpp_type_spec extends runtime_cpp_spec
{
	public string $type_name = '';
	public string $modifier_name = '';
	/** Physical interface only; selects a declared parameter or the modifier payload. */
	public string $pointee_parameter = '';
	public bool $pointee_modified_type = false;
	public bool $concrete_boundary = false;
	public string $literal_interface = '';
	public string $byte_interface = '';
	public ?Storage $arguments /** Storage<runtime_cpp_argument_spec> */ = null;
}

final class runtime_cpp_argument_spec
{
	public string $native_name = '';
	public string $parameter = '';
	public bool $modified_type = false;
	public bool $payload = false;
	public string $header = '';
}

/** Bounded scalar constants; values retain normalized exact decimal spelling. */
final class runtime_constant_spec
{
	public ?runtime_type_spec $owner = null;
	public string $name = '';
	public string $type = '';
	public string $decimal = '';
}

final class runtime_property_spec
{
	public string $identity = '';
	public string $name = '';
	public runtime_type_spec $owner;
	public runtime_type_spec $type;
	public bool $static_member = false;
	public storage_access $access = storage_access::writable;
}

final class runtime_module_spec
{
	public runtime_cpp_build_spec $cpp_build;
	public Keyed_Storage $cpp_initialization /** Keyed_Storage<runtime_cpp_spec> */;
	public string $name = '';
	public string $file = '';
	public array $dependencies /** vector<string> */ = [];
	public Storage $functions /** Storage<runtime_function_spec> */;
	public Storage $constants /** Storage<runtime_constant_spec> */;
	public Storage $properties /** Storage<runtime_property_spec> */;
	public Keyed_Storage $cpp_properties /** Keyed_Storage<runtime_cpp_spec> */;
	public Storage $types /** Storage<runtime_type_definition_spec> */;
	public Keyed_Storage $cpp_types /** Keyed_Storage<runtime_cpp_type_spec> */;
	public Keyed_Storage $cpp /** Keyed_Storage<runtime_cpp_spec> */;
	public function __construct()
	{
		$this->cpp_build = new runtime_cpp_build_spec();
		$this->functions = new Storage /** Storage<runtime_function_spec> */();
		$this->cpp_initialization = new Keyed_Storage /** Keyed_Storage<runtime_cpp_spec> */();
		$this->constants = new Storage /** Storage<runtime_constant_spec> */();
		$this->properties = new Storage /** Storage<runtime_property_spec> */();
		$this->cpp_properties = new Keyed_Storage /** Keyed_Storage<runtime_cpp_spec> */();
		$this->types = new Storage /** Storage<runtime_type_definition_spec> */();
		$this->cpp_types = new Keyed_Storage /** Keyed_Storage<runtime_cpp_type_spec> */();
		$this->cpp = new Keyed_Storage /** Keyed_Storage<runtime_cpp_spec> */();
	}
}

/** Portable build requirements, independent of source AST and native cache state. */
final class runtime_cpp_build_spec
{
	/** Named host-resolved SDK requirements; executable metadata is forbidden. */
	public array $providers /** vector<string> */ = [];
	/** Host-resolved arguments, never accepted from module JSON. */
	public array $compile_options /** vector<string> */ = [];
	public array $link_options /** vector<string> */ = [];
	public array $diagnostics /** vector<string> */ = [];
	public array $sources /** vector<string> */ = [];
	public array $libraries /** vector<string> */ = [];
	public array $definitions /** vector<string> */ = [];
}

/** Immutable-by-contract startup input, retained across semantic registry resets. */
final class runtime_catalog_input
{
	public Keyed_Storage $modules /** Keyed_Storage<runtime_module_spec> */;
	public function __construct()
	{
		$this->modules = new Keyed_Storage /** Keyed_Storage<runtime_module_spec> */();
	}
}
