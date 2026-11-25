<?php
namespace scpp\compiler;

/** Typed constant selection facts; no syntax or backend spelling is retained. */
abstract class prepared_selection_constant
{
	public canonical_type_use $type;
	abstract public function value_key(): string;
}

final class prepared_integer_selection extends prepared_selection_constant
{
	public string $decimal;
	public function value_key(): string { return 'integer:' . $this->decimal; }
}

final class prepared_enum_selection extends prepared_selection_constant
{
	public prepared_enum_case $selected_case;
	// Labels have already been checked against one exact selector type.
	public function value_key(): string { return 'case:' . $this->selected_case->name; }
}
