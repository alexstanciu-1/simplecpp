<?php
namespace scpp\compiler;

/** Argument-selected semantic facts; no general user-template instantiation is implied. */
abstract class prepared_compiler_call
{
	abstract public function kind(): compiler_function_kind;
}

final class prepared_enum_call extends prepared_compiler_call
{
	public prepared_enum $enumeration;
	public canonical_type_use $result_type;
	public canonical_type_use $input_type;

	public function __construct(private compiler_function_kind $operation) {}

	public function kind(): compiler_function_kind
	{
		return $this->operation;
	}
}
