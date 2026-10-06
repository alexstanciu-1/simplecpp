<?php
namespace scpp\compiler;

/** Argument-selected semantic facts; no general user-template instantiation is implied. */
abstract class prepared_compiler_call
{
	abstract public function kind(): compiler_function_kind;
}

final class prepared_enum_name_call extends prepared_compiler_call
{
	public prepared_enum $enumeration;
	public canonical_type_use $result_type;

	public function kind(): compiler_function_kind
	{
		return compiler_function_kind::enum_name;
	}
}
