<?php

/* Constant identities and settled values are independent of backend spelling. */
namespace scpp\compiler;

interface constant_target_i extends preparation_lookup_candidate_i
{
	public function constant_name(): string;
	/** Read settled facts only; analysis owns prerequisite completion. */
	public function value(): constant_value;
}

final class registered_constant implements constant_target_i
{
	public function __construct(private string $source_name, private constant_value $stored_value,
		private ?canonical_type_use $owner_type = null) {}
	public function owner(): ?canonical_type_use { return $this->owner_type; }
	public function constant_name(): string { return $this->source_name; }
	public function value(): constant_value { return $this->stored_value; }
}

/** Values are immutable after publication and contain no syntax/definition backlinks. */
abstract class constant_value
{
	public canonical_type_use $type;
	abstract public function value_key(): string;
}

final class float_constant_value extends constant_value
{
	public string $decimal;
	public function value_key(): string { return $this->decimal; }
}

final class boolean_constant_value extends constant_value
{
	public bool $value;
	public function value_key(): string { return $this->value ? '1' : '0'; }
}

final class string_constant_value extends constant_value
{
	public string $value;
	public function value_key(): string { return $this->value; }
}

/** Exact signed integer value retained as decimal text to avoid host-width conversion. */
final class integer_constant_value extends constant_value
{
	public string $decimal;
	public function value_key(): string { return $this->decimal; }
}

/** A singleton runtime tag: its canonical type uniquely identifies its value. */
final class sentinel_constant_value extends constant_value
{
	public function value_key(): string { return ''; }
}

/** Immutable published no-error value; ordinary error locals remain assignable. */
final class no_error_constant_value extends constant_value
{
	public function value_key(): string { return ''; }
}

/** Enum identity and an immutable case value, without a source syntax backlink. */
final class enum_constant_value extends constant_value
{
	public prepared_enum_case $selected_case;
	public function value_key(): string
	{
		return $this->selected_case->name . ':' . $this->selected_case->decimal;
	}
}
