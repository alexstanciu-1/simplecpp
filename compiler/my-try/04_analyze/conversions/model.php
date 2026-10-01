<?php

/* Backend-neutral decisions for typed expression boundaries. */
namespace scpp\compiler;

enum conversion_context
{
	case assignment;
	case argument;
	case return_value;
}

enum conversion_operation
{
	case identity;
	case integer_value_cast;
}

/** Retained semantic result of comparing one produced value with its consumer. */
final class conversion_decision
{
	public canonical_type_use $source_type;
	public canonical_type_use $target_type;
	public canonical_type_use $result_type;
	public conversion_context $context;
	public conversion_operation $operation;

	public function requires_cast(): bool
	{
		return $this->operation !== conversion_operation::identity;
	}
}
