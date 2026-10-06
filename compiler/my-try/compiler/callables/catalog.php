<?php

/* Installed runtime identities live for the canonical type registry's lifetime. */
namespace scpp\compiler;

final class Runtime_Callables
{
	private Keyed_Storage $definitions /** Keyed_Storage<registered_static_factory> */;
	private Keyed_Storage $functions /** Keyed_Storage<registered_function> */;

	public function __construct()
	{
		$this->definitions = new Keyed_Storage /** Keyed_Storage<registered_static_factory> */();
		$this->functions = new Keyed_Storage /** Keyed_Storage<registered_function> */();
	}

	/** Own a free-function identity independently of scope indexing and backend binding. */
	public function register_function(string $key, string $name, callable_signature $signature): registered_function
	{
		$this->require_unused_key($key);
		Callable_Contracts::validate($signature);
		$target = new registered_function($key, $name, $signature);
		$functions /** Keyed_Storage<registered_function> */ = $this->functions;
		$functions->add($key, $target);
		return $target;
	}

	public function function_definition(string $key): registered_function
	{
		$functions /** Keyed_Storage<registered_function> */ = $this->functions;
		return $functions[$key];
	}

	private function require_unused_key(string $key): void
	{
		$definitions /** Keyed_Storage<registered_static_factory> */ = $this->definitions;
		$functions /** Keyed_Storage<registered_function> */ = $this->functions;
		if (isset($definitions[$key]) || isset($functions[$key])) {
			throw new \LogicException('Duplicate runtime callable identity');
		}
	}

	/** Keys are provider-qualified semantic identities, not C++ names or source lookup names. */
	public function register_static_factory(string $key, canonical_type_use $owner,
		callable_signature $signature): registered_static_factory
	{
		$definitions /** Keyed_Storage<registered_static_factory> */ = $this->definitions;
		$this->require_unused_key($key);
		Callable_Contracts::validate($signature);
		$target = new registered_static_factory($key, $owner, $signature);
		$definitions->add($key, $target);
		return $target;
	}

	public function definition(string $key): registered_static_factory
	{
		$definitions /** Keyed_Storage<registered_static_factory> */ = $this->definitions;
		return $definitions[$key];
	}
}

/** Structural admission shared by source and installed contracts; not runtime construction. */
final class Callable_Contracts
{
	public static function validate(callable_signature $signature): void
	{
		self::validate_reference_return($signature);
		$optional_seen = false;
		$variadic_seen = false;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		foreach ($parameters as $parameter) {
			if ($parameter->access === storage_access::read_only) {
				if (($parameter->mode !== passing_mode::reference) || ($parameter->element_type !== null)
					|| ($parameter->default_value !== null)) {
					throw new \RuntimeException('S2S const parameters require a fixed reference without a default');
				}
			}
			if ($variadic_seen) {
				throw new \RuntimeException('S2S variadic parameter must be last');
			}
			$variadic_seen = $parameter->element_type !== null;
			if ($variadic_seen) {
				if (($parameter->mode !== passing_mode::value) || ($parameter->default_value !== null)) {
					throw new \RuntimeException('S2S variadic parameters must be by value without an explicit default');
				}
			}
			if ($parameter->default_value !== null) {
				if ($parameter->mode !== passing_mode::value) {
					throw new \RuntimeException('S2S reference parameter defaults are deferred');
				}
				$optional_seen = true;
			}
			elseif ($optional_seen && !$variadic_seen) {
				throw new \RuntimeException('S2S required parameter cannot follow an optional parameter');
			}
		}
	}

	/** Structural contract only; proving a source body's return origin is analysis work. */
	private static function validate_reference_return(callable_signature $signature): void
	{
		if ($signature->reference_return === null) {
			return;
		}
		$return_contract /** reference_return_contract */ = $signature->reference_return;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$index = $return_contract->parameter_index();
		if ($index >= q_count($parameters)) {
			throw new \RuntimeException('Reference return names an absent parameter');
		}
		$parameter = $parameters[$index];
		if ($parameter->access === storage_access::read_only) {
			throw new \RuntimeException('S2S const-reference returns are deferred');
		}
		if (($parameter->mode !== passing_mode::reference) || ($parameter->element_type !== null)) {
			throw new \RuntimeException('Reference return requires a fixed reference parameter');
		}
		if (!$parameter->type->matches($signature->return_type)) {
			throw new \RuntimeException('Reference return requires the exact parameter storage type');
		}
	}
}
