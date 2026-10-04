<?php

/* Backend-neutral operator identities and selected semantic shape. */
namespace scpp\compiler;

enum operator_context {
	case expression;
}

/** Ordinary converted stores and updates preserving a runtime payload kind differ. */
enum compound_write_back {
	case converted_assignment;
	case mixed_stored_kind;
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
	case nullable_is_null;
	case nullable_not_null;
	case integer_bitwise_and;
	case integer_bitwise_or;
	case integer_bitwise_xor;
	case integer_shift_left;
	case integer_shift_right;
	case integer_pre_increment;
	case integer_post_increment;
	case integer_pre_decrement;
	case integer_post_decrement;
	case mixed_pre_increment;
	case mixed_post_increment;
	case mixed_pre_decrement;
	case mixed_post_decrement;
	case integer_positive;
	case integer_negative;
	case integer_complement;
	case boolean_not;
	case condition_not;
	case mixed_positive;
	case mixed_negative;
	case mixed_addition;
	case mixed_subtraction;
	case mixed_multiplication;
	case mixed_division;
	case mixed_remainder;
	case mixed_equal;
	case mixed_not_equal;
	case mixed_identical;
	case mixed_not_identical;
	case mixed_less;
	case mixed_less_equal;
	case mixed_greater;
	case mixed_greater_equal;
	case integer_three_way;
	case boolean_xor;
	case boolean_and;
	case boolean_or;
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
	case integer_not_identical;
	case integer_less;
	case integer_less_equal;
	case integer_greater;
	case integer_greater_equal;
}

/** One selected operator shape with ordered operand conversions and result type. */
final class operator_decision {
	public ?truthiness_decision $truthiness = null;
	public operator_kind $source_operator;
	public operator_operation $operation;
	public array $operands /** vector<conversion_decision> */ = [];
	public canonical_type_use $result_type;
}
