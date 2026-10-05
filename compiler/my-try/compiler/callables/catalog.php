<?php

/* Installed runtime identities live for the canonical type registry's lifetime. */
namespace scpp\compiler;

final class Runtime_Callables
{
	private Keyed_Storage $definitions /** Keyed_Storage<registered_static_factory> */;

	public function __construct()
	{
		$this->definitions = new Keyed_Storage /** Keyed_Storage<registered_static_factory> */();
	}

	/** Keys are provider-qualified semantic identities, not C++ names or source lookup names. */
	public function register_static_factory(string $key, canonical_type_use $owner,
		callable_signature $signature): registered_static_factory
	{
		$definitions /** Keyed_Storage<registered_static_factory> */ = $this->definitions;
		if (isset($definitions[$key])) {
			throw new \LogicException('Duplicate runtime callable identity');
		}
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
		$optional_seen = false;
		$variadic_seen = false;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		foreach ($parameters as $parameter) {
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
}
