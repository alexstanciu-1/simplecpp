<?php

/* Installed declarations and instantiated contracts share the type-registry lifetime. */
namespace scpp\compiler;

final class Initialization_Contracts
{
	private Storage $declarations /** Storage<initialization_declaration> */;
	private Keyed_Storage $bindings /** Keyed_Storage<initialization_binding> */;
	private template_type_definition $sequence_definition;
	private template_type_definition $entry_definition;
	private bool $published = false;

	public function __construct(Type_Registry $registry, canonical_type_use $string_type)
	{
		$this->declarations = new Storage /** Storage<initialization_declaration> */();
		$this->bindings = new Keyed_Storage /** Keyed_Storage<initialization_binding> */();
		$copyable_storage /** vector<generic_contract> */ = [generic_contract::copyable_value, generic_contract::value_storable];
		$no_capabilities /** vector<generic_contract> */ = [];
		$sequence_parameters /** vector<template_type_parameter> */ = [new template_type_parameter('Value', $copyable_storage)];
		$entry_parameters /** vector<template_type_parameter> */ = [
			new template_type_parameter('Value', $copyable_storage),
			new template_type_parameter('Key', $copyable_storage, $string_type),
		];
		// Internal identities only: source exposure waits for enforced non-escape.
		$this->sequence_definition = $registry->define_template('initializer_list', type_definition_origin::runtime,
			nominal_type_kind::structure, $sequence_parameters, $no_capabilities);
		$this->entry_definition = $registry->define_template('initializer_entry', type_definition_origin::runtime,
			nominal_type_kind::structure, $entry_parameters, $copyable_storage);
	}

	public function sequence(): template_type_definition { return $this->sequence_definition; }
	public function entry(): template_type_definition { return $this->entry_definition; }

	/** Explicit lifetime/immutability roles, independent of names and storage representation. */
	public function is_call_scoped(canonical_type_i $type): bool
	{
		return $type->definition() === $this->sequence_definition;
	}

	public function is_readonly_input(canonical_type_i $type): bool
	{
		return ($type->definition() === $this->sequence_definition) || ($type->definition() === $this->entry_definition);
	}

	public function register(initialization_declaration $declaration): void
	{
		if ($this->published) { throw new \LogicException('Initialization declarations require reset after publication'); }
		$declarations /** Storage<initialization_declaration> */ = $this->declarations;
		$declarations->append($declaration);
	}

	public function candidates(): Storage /** Storage<initialization_declaration> */ { return $this->declarations; }

	public function existing(canonical_type_use $target, bool $keyed): ?initialization_binding
	{
		$bindings /** Keyed_Storage<initialization_binding> */ = $this->bindings;
		$key = self::binding_key($target, $keyed);
		if (!isset($bindings[$key])) { return null; }
		return $bindings[$key];
	}

	public function publish(initialization_binding $binding): void
	{
		$bindings /** Keyed_Storage<initialization_binding> */ = $this->bindings;
		$bindings->add(self::binding_key($binding->target(), $binding->is_keyed()), $binding);
		$this->published = true;
	}

	public static function binding_key(canonical_type_use $target, bool $keyed): string
	{
		return $target->type_id() . ':' . ($target->by_value() ? 'value' : 'default') . ':' . ($keyed ? 'keyed' : 'list');
	}
}

/** All built-in differences are declarations, not algorithms in the binder. */
final class Initialization_Registration
{
	public static function install(registered_type_catalog $types): Initialization_Contracts
	{
		$registry = $types->registry();
		$string_type = $registry->use($types->canonical('string')->type_id());
		$integer = $registry->use($types->canonical('int')->type_id());
		$mixed = $registry->use($types->canonical('mixed')->type_id());
		$contracts = new Initialization_Contracts($registry, $string_type);
		$value = initialization_type_reference::argument(0);
		$key = initialization_type_reference::argument(1);
		$boxed = initialization_type_reference::concrete($mixed);
		$unconstrained /** hash<canonical_type_use, int> */ = [];
		$integer_key /** hash<canonical_type_use, int> */ = [1 => $integer];
		$string_key /** hash<canonical_type_use, int> */ = [1 => $string_type];
		$contracts->register(new initialization_declaration($types->definition('vector'), $value, null, $value,
			$unconstrained, 'simple_cpp.vector.list'));
		$contracts->register(new initialization_declaration($types->definition('hash'), $value, null, $value,
			$integer_key, 'simple_cpp.hash.list'));
		$contracts->register(new initialization_declaration($types->definition('hash'), $value, $key, $value,
			$integer_key, 'simple_cpp.hash.keyed'));
		$contracts->register(new initialization_declaration($types->definition('hash'), $value, $key, $value,
			$string_key, 'simple_cpp.hash.keyed'));
		foreach (['mixed', 'dynamic'] as $name) {
			$nested = initialization_type_reference::concrete($registry->use($types->canonical($name)->type_id()));
			$contracts->register(new initialization_declaration($types->definition($name), $boxed, null, $nested,
				$unconstrained, 'simple_cpp.' . $name . '.list'));
			$contracts->register(new initialization_declaration($types->definition($name), $boxed, $boxed, $nested,
				$unconstrained, 'simple_cpp.' . $name . '.keyed'));
		}
		return $contracts;
	}
}
