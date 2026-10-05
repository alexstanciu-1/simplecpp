<?php

/* Registered read signatures; neither syntax nor backend allocation belongs here. */
namespace scpp\compiler;

final class index_read_declaration
{
	public function __construct(private type_definition_i $receiver_definition,
		private contract_type_reference $key_reference, private contract_type_reference $value_reference,
		private operator_operation $selected_operation)
	{
		if (($selected_operation !== operator_operation::index_checked_read)
			&& ($selected_operation !== operator_operation::index_null_read)) {
			throw new \InvalidArgumentException('Index read contract requires a value-read operation');
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
}

/** Immutable registry-lifetime inventory, separate from initialization opt-in. */
final class Index_Read_Contracts
{
	public function __construct(private Storage $declarations /** Storage<index_read_declaration> */)
	{
	}

	/** Exact definition identity is the capability; spelling and representation confer none. */
	public function lookup(type_definition_i $definition): ?index_read_declaration
	{
		foreach ($this->declarations as $declaration) {
			if ($declaration->definition() === $definition) {
				return $declaration;
			}
		}
		return null;
	}
}

final class Index_Read_Registration
{
	/** Family differences are installed signatures, not separate preparation paths. */
	public static function install(registered_type_catalog $types): Index_Read_Contracts
	{
		$registry = $types->registry();
		$integer = contract_type_reference::concrete($registry->use($types->canonical('int')->type_id()));
		$mixed = contract_type_reference::concrete($registry->use($types->canonical('mixed')->type_id()));
		$value = contract_type_reference::argument(0);
		$key = contract_type_reference::argument(1);
		$declarations /** Storage<index_read_declaration> */ = new Storage();
		$declarations->append(new index_read_declaration($types->definition('vector'), $integer, $value,
			operator_operation::index_checked_read));
		$declarations->append(new index_read_declaration($types->definition('hash'), $key, $value,
			operator_operation::index_checked_read));
		foreach (['mixed', 'dynamic'] as $name) {
			$declarations->append(new index_read_declaration($types->definition($name), $mixed, $mixed,
				operator_operation::index_null_read));
		}
		return new Index_Read_Contracts($declarations);
	}
}
