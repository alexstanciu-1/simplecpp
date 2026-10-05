<?php

/* Registered bracket access contracts; neither syntax nor backend allocation belongs here. */
namespace scpp\compiler;

enum index_append_policy { case unsupported; case allowed; case integer_key; }

final class indexing_declaration
{
	public function __construct(private type_definition_i $receiver_definition,
		private contract_type_reference $key_reference, private contract_type_reference $value_reference,
		private operator_operation $selected_operation,
		private ?operator_operation $store_operation,
		private index_append_policy $append_rule, private contract_type_reference $nested_reference,
		private ?contract_type_reference $path_reference,
		private bool $probe_enabled, private bool $erase_enabled)
	{
		if (($selected_operation !== operator_operation::index_checked_read)
			&& ($selected_operation !== operator_operation::index_null_read)) {
			throw new \InvalidArgumentException('Index read contract requires a value-read operation');
		}
		if ($store_operation !== null) {
			if ($store_operation !== operator_operation::index_store) {
				throw new \InvalidArgumentException('Index write contract requires an indexed store operation');
			}
		}
	}

	public function definition(): type_definition_i
	{
		return $this->receiver_definition;
	}

	public function key(): contract_type_reference
	{
		return $this->key_reference;
	}

	public function value(): contract_type_reference
	{
		return $this->value_reference;
	}

	public function operation(): operator_operation
	{
		return $this->selected_operation;
	}

	public function append_policy(): index_append_policy
	{
		return $this->append_rule;
	}

	public function write_operation(): ?operator_operation
	{
		return $this->store_operation;
	}

	public function nested(): contract_type_reference
	{
		return $this->nested_reference;
	}

	/** Path traversal is distinct from a copied value read or literal context. */
	public function path_element(): ?contract_type_reference
	{
		return $this->path_reference;
	}

	public function supports_probes(): bool { return $this->probe_enabled; }
	public function supports_removal(): bool { return $this->erase_enabled; }
}

/** Immutable registry-lifetime inventory, separate from initialization opt-in. */
final class Indexing_Contracts
{
	public function __construct(private Storage $declarations /** Storage<indexing_declaration> */)
	{
	}

	/** Exact definition identity is the capability; spelling and representation confer none. */
	public function lookup(type_definition_i $definition): ?indexing_declaration
	{
		foreach ($this->declarations as $declaration) {
			if ($declaration->definition() === $definition) {
				return $declaration;
			}
		}
		return null;
	}
}

final class Indexing_Registration
{
	/** Family differences are installed signatures, not separate preparation paths. */
	public static function install(registered_type_catalog $types): Indexing_Contracts
	{
		$registry = $types->registry();
		$integer = contract_type_reference::concrete($registry->use($types->canonical('int')->type_id()));
		$mixed = contract_type_reference::concrete($registry->use($types->canonical('mixed')->type_id()));
		$value = contract_type_reference::argument(0);
		$key = contract_type_reference::argument(1);
		$declarations /** Storage<indexing_declaration> */ = new Storage();
		$declarations->append(new indexing_declaration($types->definition('vector'), $integer, $value,
			operator_operation::index_checked_read, operator_operation::index_store, index_append_policy::allowed, $value, $value, true, false));
		$declarations->append(new indexing_declaration($types->definition('hash'), $key, $value,
			operator_operation::index_checked_read, operator_operation::index_store, index_append_policy::integer_key, $value, $value, true, true));
		foreach (['mixed', 'dynamic'] as $name) {
			$nested = contract_type_reference::concrete($registry->use($types->canonical($name)->type_id()));
			$declarations->append(new indexing_declaration($types->definition($name), $mixed, $mixed,
				operator_operation::index_null_read, operator_operation::index_store, index_append_policy::allowed, $nested, $nested, true, true));
		}
		return new Indexing_Contracts($declarations);
	}
}
