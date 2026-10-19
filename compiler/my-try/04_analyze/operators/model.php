<?php

/* Backend-neutral operator identities and selected semantic shape. */
namespace scpp\compiler;

enum operator_context {
	case expression;
	case compound_assignment;
}

/** Ordinary converted stores and updates preserving a runtime payload kind differ. */
enum compound_write_back {
	case converted_assignment;
	case boxed_stored_kind;
}

enum operator_kind {
	case index;
	case bitwise_and;
	case bitwise_or;
	case bitwise_xor;
	case shift_left;
	case shift_right;
	case pre_increment;
	case post_increment;
	case pre_decrement;
	case post_decrement;
	case unary_plus;
	case unary_minus;
	case bitwise_not;
	case logical_not;
	case three_way;
	case logical_xor;
	case logical_and;
	case logical_or;
	case concatenation;
	case addition;
	case subtraction;
	case power;
	case multiplication;
	case division;
	case remainder;
	case equal;
	case not_equal;
	case identical;
	case not_identical;
	case less;
	case less_equal;
	case greater;
	case greater_equal;
}

enum operator_operation {
	case wrapper_equal;
	case wrapper_not_equal;
	case wrapper_identical;
	case wrapper_not_identical;
	case string_equal;
	case string_not_equal;
	case string_less;
	case string_less_equal;
	case string_greater;
	case string_greater_equal;
	case string_three_way;

	case boolean_equal;
	case boolean_not_equal;
	case floating_addition;
	case floating_subtraction;
	case floating_multiplication;
	case floating_division;
	case floating_positive;
	case floating_negative;
	case floating_equal;
	case floating_not_equal;
	case floating_less;
	case floating_less_equal;
	case floating_greater;
	case floating_greater_equal;
	case enum_equal;
	case enum_not_equal;
	case index_checked_read;
	case index_null_read;
	case index_store;
	case index_append;
	case presence_is_null;
	case presence_not_null;
	case integer_bitwise_and;
	case integer_bitwise_or;
	case integer_bitwise_xor;
	case integer_shift_left;
	case integer_shift_right;
	case scalar_pre_increment;
	case scalar_post_increment;
	case scalar_pre_decrement;
	case scalar_post_decrement;
	case wrapper_pre_increment;
	case wrapper_post_increment;
	case wrapper_pre_decrement;
	case wrapper_post_decrement;
	case boxed_pre_increment;
	case boxed_post_increment;
	case boxed_pre_decrement;
	case boxed_post_decrement;
	case integer_positive;
	case integer_negative;
	case integer_complement;
	case boolean_not;
	case condition_not;
	case boxed_positive;
	case boxed_negative;
	case boxed_addition;
	case boxed_subtraction;
	case boxed_multiplication;
	case boxed_division;
	case boxed_remainder;
	case boxed_equal;
	case boxed_not_equal;
	case boxed_identical;
	case boxed_not_identical;
	case boxed_less;
	case boxed_less_equal;
	case boxed_greater;
	case boxed_greater_equal;
	case integer_three_way;
	case boolean_xor;
	case boolean_and;
	case boolean_or;
	case condition_and;
	case condition_or;
	case condition_xor;
	case string_concatenation;
	case integer_addition;
	case integer_subtraction;
	case integer_power;
	case integer_multiplication;
	case integer_division;
	case integer_remainder;
	case integer_equal;
	case integer_not_equal;
	case integer_identical;
	case scalar_identical;
	case scalar_not_identical;
	case integer_not_identical;
	case integer_less;
	case integer_less_equal;
	case integer_greater;
	case integer_greater_equal;
}

/** Selected operation, result type and ordered conversions or condition truthiness. */
final class operator_decision {
	/** Empty for ordinary operators; otherwise one extraction per source operand before payload conversion/truthiness. */
	public array $operand_extractions /** vector<conversion_decision> */ = [];
	public ?truthiness_decision $truthiness = null;
	public array $operand_truthiness /** vector<truthiness_decision> */ = [];
	public operator_kind $source_operator;
	public operator_operation $operation;
	public array $operands /** vector<conversion_decision> */ = [];
	public canonical_type_use $result_type;
}
