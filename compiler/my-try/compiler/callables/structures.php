<?php

/* Callable contracts have no source-storage requirement or backend spelling. */
namespace scpp\compiler;

/** Lookup identity need not have an argument-independent signature. */
interface callable_definition_i extends preparation_lookup_candidate_i {}

interface callable_target_i extends callable_definition_i
{
	/** Read an established contract; analysis owns completion and dependency registration. */
	public function signature(): callable_signature;
}

enum compiler_function_kind
{
	case async_sleep;
	case async_wait;
	case enum_name;
	case enum_value;
	case enum_from_value;
	case layout_sizeof;
	case layout_alignof;
	case layout_offsetof;
	case layout_field_sizeof;
}

/** Language-owned identity only; stage workers own selection and emission. */
final class compiler_function_definition implements callable_definition_i
{
	public function __construct(private string $source_name, private compiler_function_kind $operation) {}
	public function name(): string { return $this->source_name; }
	public function kind(): compiler_function_kind { return $this->operation; }
}

/** Catalog-owned runtime identity, independent of whether its signature needs inference. */
abstract class runtime_function_definition implements callable_definition_i
{
	/** Installed provider guarantee; never inferred from its name or native spelling. */
	public bool $snapshot_borrow_safe = false;
	private string $registration_key;
	private string $source_name;

	/** Provider keys and source lookup names are distinct nonempty identities. */
	public function __construct(string $key, string $name)
	{
		if (($key === '') || ($name === '')) {
			throw new \InvalidArgumentException('Runtime function identity and name must not be empty');
		}
		$this->registration_key = $key;
		$this->source_name = $name;
	}

	public function key(): string
	{
		return $this->registration_key;
	}

	public function name(): string
	{
		return $this->source_name;
	}
}

/** Installed concrete callable contract; optional type membership lives in the catalog. */
final class registered_function extends runtime_function_definition implements callable_target_i
{
	private callable_signature $call_contract;

	public function __construct(string $key, string $name, callable_signature $signature)
	{
		parent::__construct($key, $name);
		$this->call_contract = $signature;
	}

	public function signature(): callable_signature
	{
		return $this->call_contract;
	}
}

enum runtime_pattern_kind { case variable; case concrete; case application; case callable_type; }

/** A type-owned public callable, not a fabricated collected source declaration. */
final class runtime_callable_member
{
	public function __construct(public semantic_type_definition $owner, public runtime_function_definition $target,
		public bool $static_member)
	{
	}
}

/** Type patterns expose data identity, not source ASTs or executable matchers. */
abstract class runtime_type_pattern
{
	abstract public function kind(): runtime_pattern_kind;
}

final class runtime_variable_pattern extends runtime_type_pattern
{
	public function __construct(public int $position, public bool $void_to_null = false) {}
	public function kind(): runtime_pattern_kind { return runtime_pattern_kind::variable; }
}

final class runtime_concrete_pattern extends runtime_type_pattern
{
	public function __construct(public canonical_type_use $type) {}
	public function kind(): runtime_pattern_kind { return runtime_pattern_kind::concrete; }
}

final class runtime_application_pattern extends runtime_type_pattern
{
	public Storage $arguments /** Storage<runtime_type_pattern> */;
	public function __construct(public abstract_template_type_definition $definition)
	{
		$this->arguments = new Storage /** Storage<runtime_type_pattern> */();
	}
	public function kind(): runtime_pattern_kind { return runtime_pattern_kind::application; }
}

final class runtime_pattern_parameter
{
	public bool $requires_copy = false;
	public bool $requires_transfer = false;
	public bool $variadic = false;
	public ?prepared_literal_parameter_default $default_value = null;
	public function __construct(public runtime_type_pattern $type, public passing_mode $mode,
		public storage_access $access) {}
}

final class runtime_callable_pattern extends runtime_type_pattern
{
	public runtime_type_pattern $return_type;
	public Storage $parameters /** Storage<runtime_pattern_parameter> */;
	public function __construct() { $this->parameters = new Storage /** Storage<runtime_pattern_parameter> */(); }
	public function kind(): runtime_pattern_kind { return runtime_pattern_kind::callable_type; }
}

/** Exact result replacement or void omission, resolved to generic binding positions. */
final class runtime_replacement_requirement
{
	public int $result_position;
	public int $replacement_position;
	public function __construct(int $result_position, int $replacement_position)
	{
		$this->result_position = $result_position;
		$this->replacement_position = $replacement_position;
	}
}

/** Canonical scalar or matching boxed-family output, checked after inference. */
final class runtime_boxed_result_requirement
{
	public int $result_position;
	public canonical_type_use $family_type;
	public function __construct(int $result_position, canonical_type_use $family_type)
	{
		$this->result_position = $result_position;
		$this->family_type = $family_type;
	}
}

final class runtime_generic_contract
{
	public int $owner_parameter_count = 0;
	public int $type_parameter_count = 0;
	public array $transfer_positions /** vector<int> */ = [];
	public Storage $replacement_results /** Storage<runtime_replacement_requirement> */;
	public Storage $boxed_results /** Storage<runtime_boxed_result_requirement> */;
	public runtime_type_pattern $return_type;
	public Storage $parameters /** Storage<runtime_pattern_parameter> */;
	public function __construct()
	{
		$this->parameters = new Storage /** Storage<runtime_pattern_parameter> */();
		$this->replacement_results = new Storage /** Storage<runtime_replacement_requirement> */();
		$this->boxed_results = new Storage /** Storage<runtime_boxed_result_requirement> */();
	}
}

/** One provider identity; concrete signatures belong to prepared calls, not the catalog. */
final class registered_generic_function extends runtime_function_definition
{
	private runtime_generic_contract $pattern_contract;
	public function __construct(string $key, string $name, runtime_generic_contract $pattern_contract)
	{
		parent::__construct($key, $name);
		$this->pattern_contract = $pattern_contract;
	}
	public function contract(): runtime_generic_contract { return $this->pattern_contract; }
}

/** Published with its owning signature; never mutated after publication. */
final class callable_parameter
{
	/** Runtime provider requires copying even when the argument is moved. */
	public bool $requires_copy = false;
	public bool $requires_transfer = false;
	public canonical_type_use $type;
	public passing_mode $mode;
	public storage_access $access = storage_access::writable;
	/** For a variadic parameter, type is vector storage and element_type is its input type. */
	public ?canonical_type_use $element_type = null;
	public ?prepared_parameter_default $default_value = null;
}

/** Complete, declaration-independent contract shared by every call consumer. */
final class callable_signature
{
	/** An async body's declared value type; return_type is its caller-visible task. */
	public ?canonical_type_use $async_value_type = null;
	public canonical_type_use $return_type;
	/** Null means an ordinary value return. Reference execution is still gated. */
	public ?reference_return_contract $reference_return = null;
	public Storage $parameters /** Storage<callable_parameter> */;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<callable_parameter> */();
	}
}

/** Symbolic whole-parameter origin; never retains a particular caller or source AST. */
final class reference_return_contract
{
	private int $source_parameter;

	public function __construct(int $parameter_index)
	{
		if ($parameter_index < 0) {
			throw new \InvalidArgumentException('Reference return parameter index must be nonnegative');
		}
		$this->source_parameter = $parameter_index;
	}

	public function parameter_index(): int
	{
		return $this->source_parameter;
	}
}

/** A concrete registered static factory; no fabricated source declaration or optional AST. */
final class registered_static_factory implements callable_target_i
{
	private string $registration_key;
	private canonical_type_use $owner_type;
	private callable_signature $call_contract;

	public function __construct(string $key, canonical_type_use $owner, callable_signature $signature)
	{
		if ($key === '') {
			throw new \InvalidArgumentException('Runtime callable identity must not be empty');
		}
		if (!$owner->matches($signature->return_type)) {
			throw new \InvalidArgumentException('Static factory must return its exact owner type');
		}
		if ($signature->reference_return !== null) {
			throw new \InvalidArgumentException('Static factory must return an owned value');
		}
		$this->registration_key = $key;
		$this->owner_type = $owner;
		$this->call_contract = $signature;
	}

	public function key(): string { return $this->registration_key; }
	public function owner(): canonical_type_use { return $this->owner_type; }
	public function signature(): callable_signature
	{
		return $this->call_contract;
	}
}
