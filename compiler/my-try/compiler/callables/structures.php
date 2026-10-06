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

enum compiler_function_kind { case enum_name; }

/** Language-owned identity only; stage workers own selection and emission. */
final class compiler_function_definition implements callable_definition_i
{
	public function __construct(private string $source_name, private compiler_function_kind $operation) {}
	public function name(): string { return $this->source_name; }
	public function kind(): compiler_function_kind { return $this->operation; }
}

/** Installed free-function identity; source spelling is separate from provider identity. */
final class registered_function implements callable_target_i
{
	private string $registration_key;
	private string $source_name;
	private callable_signature $call_contract;

	/** Catalog installation publishes a complete, immutable-by-contract signature. */
	public function __construct(string $key, string $name, callable_signature $signature)
	{
		if (($key === '') || ($name === '')) {
			throw new \InvalidArgumentException('Runtime function identity and name must not be empty');
		}
		$this->registration_key = $key;
		$this->source_name = $name;
		$this->call_contract = $signature;
	}

	public function key(): string
	{
		return $this->registration_key;
	}

	public function name(): string
	{
		return $this->source_name;
	}

	public function signature(): callable_signature
	{
		return $this->call_contract;
	}
}

/** Published with its owning signature; never mutated after publication. */
final class callable_parameter
{
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
