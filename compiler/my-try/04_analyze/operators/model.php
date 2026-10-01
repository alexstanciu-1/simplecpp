<?php

/* Backend-neutral operator identities and selected semantic shape. */
namespace scpp\compiler;

enum operator_context {
	case expression;
}

enum operator_kind {
	case addition;
}

enum operator_operation {
	case integer_addition;
}

/** One selected operator shape with ordered operand conversions and result type. */
final class operator_decision {
	public operator_kind $source_operator;
	public operator_operation $operation;
	public array $operands /** vector<conversion_decision> */ = [];
	public canonical_type_use $result_type;
}
