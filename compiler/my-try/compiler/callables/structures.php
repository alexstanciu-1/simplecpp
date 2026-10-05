<?php

/* Callable contracts have no source-storage requirement or backend spelling. */
namespace scpp\compiler;

interface callable_target_i
{
	/** Source targets require completion and register dependencies; runtime contracts are installed complete. */
	public function require_signature(preparation_context $context): callable_signature;
	/** Typed backend dispatch; spelling belongs to the C++ binding owner. */
	public function cpp_reference(cpp_generation_context $context): string;
}

/** Published with its owning signature; never mutated after publication. */
final class callable_parameter
{
	public canonical_type_use $type;
	public passing_mode $mode;
	/** For a variadic parameter, type is vector storage and element_type is its input type. */
	public ?canonical_type_use $element_type = null;
	public ?prepared_parameter_default $default_value = null;
}

/** Complete, declaration-independent contract shared by every call consumer. */
final class callable_signature
{
	public canonical_type_use $return_type;
	public Storage $parameters /** Storage<callable_parameter> */;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<callable_parameter> */();
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
		$this->registration_key = $key;
		$this->owner_type = $owner;
		$this->call_contract = $signature;
	}

	public function key(): string { return $this->registration_key; }
	public function owner(): canonical_type_use { return $this->owner_type; }
	public function require_signature(preparation_context $context): callable_signature
	{
		return $this->call_contract;
	}
	public function cpp_reference(cpp_generation_context $context): string
	{
		return CPP_Callables::runtime_reference($this, $context);
	}
}
