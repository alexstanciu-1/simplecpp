<?php

/* Backend-neutral decisions for typed expression boundaries. */
namespace scpp\compiler;

enum conversion_context
{
	case assignment;
	case argument;
	case return_value;
	case explicit_cast;
	case operator_operand;
	case interpolation;
	case concatenation;
	case conditional_branch;
	case coalesce_value;
	case initializer_key;
	case container_element;
}

enum conversion_operation
{
	case identity;
	case integer_value_cast;
	case explicit_runtime_cast;
	case text_conversion;
	case nullable_empty;
	case ownership_empty;
	case nullable_wrap;
	case nullable_extract;
	case result_wrap;
	case result_false;
	case result_true;
	case result_extract;
	case boxed_wrap;
	case boxed_extract;
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

/** Condition interpretation is not a value conversion or an explicit bool cast. */
enum truthiness_operation
{
	case boolean_identity;
	case scalar_condition;
	case nullable_condition;
	case value_presence;
	case result_condition;
	case error_condition;
	case boxed_condition;
	case always_true;
}

final class truthiness_decision
{
	public canonical_type_use $source_type;
	public truthiness_operation $operation;
}
