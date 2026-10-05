<?php

/* Iteration is an independent capability, not inferred from bracket access or spelling. */
namespace scpp\compiler;

final class iteration_declaration
{
	public function __construct(private type_definition_i $receiver_definition,
		private contract_type_reference $key_reference, private contract_type_reference $value_reference)
	{
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
}

final class Iteration_Contracts
{
	public function __construct(private Storage $declarations /** Storage<iteration_declaration> */)
	{
	}

	/** Only exact registered identities confer iteration capability. */
	public function lookup(type_definition_i $definition): ?iteration_declaration
	{
		foreach ($this->declarations as $declaration) {
			if ($declaration->definition() === $definition) {
				return $declaration;
			}
		}
		return null;
	}
}

final class Iteration_Registration
{
	/** Built-in families differ in signatures, not in frontend processing paths. */
	public static function install(registered_type_catalog $types): Iteration_Contracts
	{
		$registry = $types->registry();
		$integer = contract_type_reference::concrete($registry->use($types->canonical('int')->type_id()));
		$mixed = contract_type_reference::concrete($registry->use($types->canonical('mixed')->type_id()));
		$value = contract_type_reference::argument(0);
		$key = contract_type_reference::argument(1);
		$declarations /** Storage<iteration_declaration> */ = new Storage();
		$declarations->append(new iteration_declaration($types->definition('vector'), $integer, $value));
		$declarations->append(new iteration_declaration($types->definition('hash'), $key, $value));
		foreach (['mixed', 'dynamic'] as $name) {
			$element = contract_type_reference::concrete($registry->use($types->canonical($name)->type_id()));
			$declarations->append(new iteration_declaration($types->definition($name), $mixed, $element));
		}
		return new Iteration_Contracts($declarations);
	}
}
