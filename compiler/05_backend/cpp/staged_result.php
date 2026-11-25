<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Native result storage whose value starts in a selected branch or inside an operand cleanup scope. */
final class Cpp_Staged_Result
{
	public function __construct(private readonly type_definition $type, private readonly string $name)
	{
	}

	/** Scalars can be assigned directly. Other values are constructed after their operands succeed. @param bool[] $headers */
	public function declaration(array &$headers /** array<string,bool> */): string
	{
		$type = $this->type->cpp_type();
		if ($this->type->cpp_trivial_value()) return "$type $this->name;";
		$headers['new'] = true;
		$slot = $this->name . '_slot';
		return "struct $slot { using Value = $type; union { Value value; }; bool live = false; "
			. "$slot() {} ~$slot() { if (live) value.~Value(); } } $this->name;";
	}

	/** An empty operand means a nested selection already constructed this destination. */
	public function initialize(string $value): string
	{
		if ($value === '') return '';
		if ($this->type->cpp_trivial_value()) return "$this->name = $value;";
		return "::new (&$this->name.value) " . $this->type->cpp_type() . "($value); $this->name.live = true;";
	}

	public function operand(): string
	{
		return $this->type->cpp_trivial_value() ? $this->name
			: 'static_cast<' . $this->type->cpp_type() . "&&>($this->name.value)";
	}
}
