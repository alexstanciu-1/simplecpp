<?php

/* Backend-neutral type references shared by registered operation contracts. */
namespace scpp\compiler;

enum contract_type_source: int
{
	case target = 1;
	case argument = 2;
	case concrete = 3;
}

/** A bounded type substitution, shared by every registered type-directed operation. */
final class contract_type_reference
{
	/** A reference has exactly one concrete, target or argument source. */
	public function __construct(private contract_type_source $source_kind,
		private int $argument_position, private ?canonical_type_use $concrete_data)
	{
		if (($source_kind === contract_type_source::concrete) !== ($concrete_data !== null)) {
			throw new \InvalidArgumentException('Contract concrete type reference mismatch');
		}
		if (($source_kind === contract_type_source::argument) !== ($argument_position >= 0)) {
			throw new \InvalidArgumentException('Contract argument reference mismatch');
		}
	}

	public static function target(): contract_type_reference
	{
		return new contract_type_reference(contract_type_source::target, -1, null);
	}

	public static function argument(int $position): contract_type_reference
	{
		if ($position < 0) {
			throw new \InvalidArgumentException('Negative contract type argument');
		}
		return new contract_type_reference(contract_type_source::argument, $position, null);
	}

	public static function concrete(canonical_type_use $type): contract_type_reference
	{
		return new contract_type_reference(contract_type_source::concrete, -1, $type);
	}

	public function source(): contract_type_source
	{
		return $this->source_kind;
	}

	public function position(): int
	{
		return $this->argument_position;
	}

	public function concrete_type(): canonical_type_use
	{
		return $this->concrete_data;
	}
}
