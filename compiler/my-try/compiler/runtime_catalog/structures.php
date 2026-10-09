<?php
namespace scpp\compiler;

/** Unresolved metadata owns no canonical identities or stage workers. */
final class runtime_type_spec
{
	public string $name = '';
	/** Explicit variable spelling distinguishes a type parameter from a registered type. */
	public string $parameter = '';
	public Storage $arguments /** Storage<runtime_type_spec> */;
	public function __construct()
	{
		$this->arguments = new Storage();
	}
}

final class runtime_parameter_spec
{
	public string $name = '';
	public runtime_type_spec $type;
	public passing_mode $mode = passing_mode::value;
	public storage_access $access = storage_access::writable;
}

final class runtime_function_spec
{
	public string $identity = '';
	public string $name = '';
	public array $type_parameters /** vector<string> */ = [];
	public runtime_type_spec $return_type;
	public Storage $parameters /** Storage<runtime_parameter_spec> */;
	public function __construct()
	{
		$this->parameters = new Storage();
	}
}

/** Backend metadata is separate from function semantics, joined only by identity. */
final class runtime_cpp_spec
{
	public string $identity = '';
	public string $symbol = '';
	public string $header = '';
}

/** Bounded scalar constants; values retain normalized exact decimal spelling. */
final class runtime_constant_spec
{
	public string $name = '';
	public string $type = '';
	public string $decimal = '';
}

final class runtime_module_spec
{
	public string $name = '';
	public string $file = '';
	public array $dependencies /** vector<string> */ = [];
	public Storage $functions /** Storage<runtime_function_spec> */;
	public Storage $constants /** Storage<runtime_constant_spec> */;
	public Keyed_Storage $cpp /** Keyed_Storage<runtime_cpp_spec> */;
	public function __construct()
	{
		$this->functions = new Storage();
		$this->constants = new Storage();
		$this->cpp = new Keyed_Storage();
	}
}

/** Immutable-by-contract startup input, retained across semantic registry resets. */
final class runtime_catalog_input
{
	public Keyed_Storage $modules /** Keyed_Storage<runtime_module_spec> */;
	public function __construct()
	{
		$this->modules = new Keyed_Storage();
	}
}
