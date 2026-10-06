<?php

/* Settled enum facts are immutable snapshots; source syntax and registry identity stay separate. */
namespace scpp\compiler;

final class prepared_enum_case
{
	public function __construct(public string $name, public string $decimal)
	{
	}
}

final class prepared_enum
{
	public canonical_type_use $type;
	/** Source backing semantics are distinct from the selected compact representation. */
	public ?canonical_type_use $backing_type = null;
	public integer_type_definition $storage;
	/** Owns the settled case lookup, in source order, without syntax backlinks. */
	public Keyed_Storage $cases /** Keyed_Storage<prepared_enum_case> */;

	public function __construct()
	{
		$this->cases = new Keyed_Storage /** Keyed_Storage<prepared_enum_case> */();
	}

	public function has_capability(generic_contract $capability): bool
	{
		return ($capability === generic_contract::copyable_value)
			|| ($capability === generic_contract::value_storable);
	}
}

/** The type identifies the enum; the selected case is a shared immutable value snapshot. */
final class prepared_enum_case_reference extends prepared_constant_expression
{
	public prepared_enum_case $selected_case;
	public function value_key(): string
	{
		return $this->selected_case->name . ':' . $this->selected_case->decimal;
	}
}
