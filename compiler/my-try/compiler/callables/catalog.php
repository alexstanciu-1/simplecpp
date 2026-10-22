<?php

/* Installed runtime identities live for the canonical type registry's lifetime. */
namespace scpp\compiler;

final class Runtime_Callables
{
	private Keyed_Storage $definitions /** Keyed_Storage<registered_static_factory> */;
	private Keyed_Storage $functions /** Keyed_Storage<runtime_function_definition> */;
	private Keyed_Storage $members /** Keyed_Storage<runtime_callable_member> */;
	private Key_Storage_List $member_names /** Key_Storage_List<runtime_callable_member> */;

	private Key_Storage_List $qualified_names /** Key_Storage_List<runtime_function_definition> */;

	public function __construct()
	{
		$this->qualified_names = new Key_Storage_List /** Key_Storage_List<runtime_function_definition> */();
		$this->definitions = new Keyed_Storage /** Keyed_Storage<registered_static_factory> */();
		$this->functions = new Keyed_Storage /** Keyed_Storage<runtime_function_definition> */();
		$this->members = new Keyed_Storage /** Keyed_Storage<runtime_callable_member> */();
		$this->member_names = new Key_Storage_List /** Key_Storage_List<runtime_callable_member> */();
	}

	/** Alias membership indexes the original identity; it creates no language type. */
	public function register_qualified_alias(string $qualifier, runtime_function_definition $target): void
	{
		if ($this->function_definition($target->key()) !== $target) {
			throw new \LogicException('Runtime alias target belongs to another catalog');
		}
		$names /** Key_Storage_List<runtime_function_definition> */ = $this->qualified_names;
		$key = $qualifier . '::' . $target->name();
		foreach ($names->named($key) as $existing) {
			if ($existing === $target) { throw new \RuntimeException('Duplicate runtime qualified alias'); }
		}
		$names->add($key, $target);
	}

	public function qualified_named(string $qualifier, string $name): array /** vector<callable_definition_i> */
	{
		$result /** vector<callable_definition_i> */ = [];
		$names /** Key_Storage_List<runtime_function_definition> */ = $this->qualified_names;
		foreach ($names->named($qualifier . '::' . $name) as $target) { $result[] = $target; }
		return $result;
	}

	/** Membership observes the already owned callable; it does not copy its contract. */
	public function register_member(semantic_type_definition $owner, runtime_function_definition $target, bool $static_member): void
	{
		$members /** Keyed_Storage<runtime_callable_member> */ = $this->members;
		if ($this->function_definition($target->key()) !== $target) {
			throw new \LogicException('Runtime member target belongs to another catalog');
		}
		$member = new runtime_callable_member($owner, $target, $static_member);
		$members->add($target->key(), $member);
		$names /** Key_Storage_List<runtime_callable_member> */ = $this->member_names;
		$names->add($owner->definition_id() . ':' . $target->name(), $member);
	}

	public function member_for(runtime_function_definition $target): ?runtime_callable_member
	{
		$members /** Keyed_Storage<runtime_callable_member> */ = $this->members;
		$key = $target->key();
		if (!isset($members[$key])) { return null; }
		return $members[$key];
	}

	/** Keep member names out of the free-function scope; signatures still share selection. */
	public function methods_named(canonical_type_use $owner, string $name, bool $static_member): array /** vector<callable_definition_i> */
	{
		$result /** vector<callable_definition_i> */ = [];
		$names /** Key_Storage_List<runtime_callable_member> */ = $this->member_names;
		$definition = Type_Queries::canonical($owner)->definition();
		foreach ($names->named($definition->definition_id() . ':' . $name) as $member) {
			if (($member->owner === $definition) && ($member->target->name() === $name)
				&& ($member->static_member === $static_member)) {
				$result[] = $member->target;
			}
		}
		return $result;
	}

	/** Own a free-function identity independently of scope indexing and backend binding. */
	public function register_function(string $key, string $name, callable_signature $signature): registered_function
	{
		$this->require_unused_key($key);
		Callable_Contracts::validate($signature);
		$target = new registered_function($key, $name, $signature);
		$functions /** Keyed_Storage<runtime_function_definition> */ = $this->functions;
		$functions->add($key, $target);
		return $target;
	}

	/** Install a validated generic recipe without inventing a placeholder signature. */
	public function register_generic(string $key, string $name, runtime_generic_contract $contract): registered_generic_function
	{
		$this->require_unused_key($key);
		$target = new registered_generic_function($key, $name, $contract);
		$functions /** Keyed_Storage<runtime_function_definition> */ = $this->functions;
		$functions->add($key, $target);
		return $target;
	}

	public function function_definition(string $key): runtime_function_definition
	{
		$functions /** Keyed_Storage<runtime_function_definition> */ = $this->functions;
		return $functions[$key];
	}

	private function require_unused_key(string $key): void
	{
		$definitions /** Keyed_Storage<registered_static_factory> */ = $this->definitions;
		$functions /** Keyed_Storage<runtime_function_definition> */ = $this->functions;
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
	/** Exact erased contract, independent of source capture/copy admission. */
	public static function matches_erased(callable_signature $provided, callable_signature $required): bool
	{
		if (!$provided->return_type->matches($required->return_type)) { return false; }
		if (($provided->reference_return === null) !== ($required->reference_return === null)) { return false; }
		if ($provided->reference_return !== null) {
			if ($provided->reference_return->parameter_index() !== $required->reference_return->parameter_index()) { return false; }
		}
		$inputs /** Storage<callable_parameter> */ = $provided->parameters;
		$expected /** Storage<callable_parameter> */ = $required->parameters;
		if (q_count($inputs) !== q_count($expected)) { return false; }
		foreach ($inputs as $index => $input) {
			$required_input = $expected[$index];
			if (($input->default_value !== null) || ($required_input->default_value !== null)) { return false; }
			if ($input->requires_copy !== $required_input->requires_copy) { return false; }
			if ($input->requires_transfer !== $required_input->requires_transfer) { return false; }
			if (!$input->type->matches($required_input->type) || ($input->mode !== $required_input->mode)
				|| ($input->access !== $required_input->access)) { return false; }
			if (($input->element_type === null) !== ($required_input->element_type === null)) { return false; }
			if ($input->element_type !== null) {
				$element /** canonical_type_use */ = $input->element_type;
				if (!$element->matches($required_input->element_type)) { return false; }
			}
		}
		return true;
	}

	public static function validate(callable_signature $signature): void
	{
		self::validate_reference_return($signature);
		$optional_seen = false;
		$variadic_seen = false;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		foreach ($parameters as $parameter) {
			if ($parameter->requires_copy && (($parameter->mode !== passing_mode::value) || ($parameter->element_type !== null))) {
				throw new \RuntimeException('Runtime copy requirements need fixed value parameters');
			}
			if ($parameter->requires_transfer && (($parameter->mode !== passing_mode::value)
				|| ($parameter->element_type !== null) || ($parameter->default_value !== null))) {
				throw new \RuntimeException('Runtime transfer requirements need fixed callable value parameters without defaults');
			}
			if ($parameter->mode === passing_mode::observation)
			{
				if ((q_count($parameters) !== 1) || ($parameter->access !== storage_access::read_only)
					|| ($parameter->default_value !== null) || ($parameter->element_type !== null)
					|| ($signature->reference_return !== null)) {
					throw new \RuntimeException('Runtime observation requires one fixed readonly input and a value return');
				}
				continue;
			}
			if ($parameter->access === storage_access::read_only) {
				if (($parameter->element_type === null) && ($parameter->mode !== passing_mode::reference)) {
					throw new \RuntimeException('S2S const parameters require a fixed reference or a value pack');
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
