<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/test_emitter.php';

om_run_emitter_regression_test_suite();

function om_run_emitter_regression_test_suite(): void
{
	om_assert_if_condition_emits_statement_form();
	om_assert_cast_bool_uses_explicit_cast_form();
	om_assert_cast_int_uses_explicit_cast_form();
	om_assert_cast_float_uses_explicit_cast_form();
	om_assert_cast_string_uses_explicit_cast_form();
	om_assert_add_assign_uses_plain_compound_plus_form();
	om_assert_add_assign_keyed_element_uses_dim_compound_plus_form();
	om_assert_add_uses_plain_binary_plus_form();
	om_assert_modulo_uses_plain_percent_form();
	om_assert_bitwise_and_uses_plain_amp_form();
	om_assert_shift_left_uses_plain_double_angle_form();
	om_assert_shift_left_assign_uses_plain_compound_shift_form();
	om_assert_logical_and_uses_plain_double_amp_form();
	om_assert_logical_or_uses_plain_double_pipe_form();
	om_assert_equal_uses_plain_double_equals_form();
	om_assert_not_equal_uses_plain_bang_equals_form();
	om_assert_identical_uses_plain_triple_equals_form();
	om_assert_not_identical_uses_plain_bang_triple_equals_form();
	om_assert_less_than_uses_plain_angle_bracket_form();
	om_assert_less_than_or_equal_uses_plain_lte_form();
	om_assert_greater_than_uses_plain_angle_bracket_form();
	om_assert_greater_than_or_equal_uses_plain_gte_form();
	om_assert_logical_not_uses_plain_bang_form();
	om_assert_post_increment_uses_postfix_form();
	echo "Emitter regression tests passed.\n";
}

function om_assert_if_condition_emits_statement_form(): void
{
	$seed = [
		'seed_id' => 'seed|condition_truthiness|if_condition|mixed_t|mixed.null|-|-|-|-',
		'item_id' => 'if_condition',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Mixed null now participates in condition context as false.',
		'operands' => [
			'lhs' => [
				'type' => 'mixed_t',
				'profile' => 'mixed.null',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'if ($lhs) {')
		|| !str_contains($source, 'var_dump(true);')
		|| !str_contains($source, '} else {')
		|| !str_contains($source, 'var_dump(false);')) {
		throw new RuntimeException('if_condition emitter must use statement-form condition lowering.');
	}
	if (str_contains($source, 'var_dump(((bool) $lhs) ? true : false);')) {
		throw new RuntimeException('if_condition emitter regressed to ternary bool-cast lowering.');
	}
}

function om_assert_cast_bool_uses_explicit_cast_form(): void
{
	$seed = [
		'seed_id' => 'seed|casts_explicit|cast_bool|string_t|string.bool_false_literal|-|-|-|-',
		'item_id' => 'cast_bool',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Strict string-to-bool cast accepts false literals.',
		'operands' => [
			'lhs' => [
				'type' => 'string_t',
				'profile' => 'string.bool_false_literal',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump((bool) $lhs);')) {
		throw new RuntimeException('cast_bool emitter must use explicit bool-cast form.');
	}
}

function om_assert_cast_int_uses_explicit_cast_form(): void
{
	$seed = [
		'seed_id' => 'seed|casts_explicit|cast_int|float_t|float.nonzero|-|-|-|-',
		'item_id' => 'cast_int',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Strict float-to-int cast truncates toward zero.',
		'operands' => [
			'lhs' => [
				'type' => 'float_t',
				'profile' => 'float.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump((int) $lhs);')) {
		throw new RuntimeException('cast_int emitter must use explicit int-cast form.');
	}
}

function om_assert_cast_float_uses_explicit_cast_form(): void
{
	$seed = [
		'seed_id' => 'seed|casts_explicit|cast_float|int_t|int.nonzero|-|-|-|-',
		'item_id' => 'cast_float',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Strict int-to-float cast widens via the explicit float cast surface.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump((float) $lhs);')) {
		throw new RuntimeException('cast_float emitter must use explicit float-cast form.');
	}
}

function om_assert_cast_string_uses_explicit_cast_form(): void
{
	$seed = [
		'seed_id' => 'seed|casts_explicit|cast_string|bool_t|bool.true|-|-|-|-',
		'item_id' => 'cast_string',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Strict bool-to-string cast uses explicit string cast form.',
		'operands' => [
			'lhs' => [
				'type' => 'bool_t',
				'profile' => 'bool.true',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump((string) $lhs);')) {
		throw new RuntimeException('cast_string emitter must use explicit string-cast form.');
	}
}

function om_assert_add_uses_plain_binary_plus_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_binary_arithmetic|add|int_t|int.nonzero|float_t|float.nonzero|-|-',
		'item_id' => 'add',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Binary add should emit the plain + operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
			'rhs' => [
				'type' => 'float_t',
				'profile' => 'float.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs + $rhs);')) {
		throw new RuntimeException('add emitter must use plain binary + form.');
	}
}

function om_assert_add_assign_uses_plain_compound_plus_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_compound_assignment|add_assign|int_t|int.nonzero|int_t|int.zero|-|-',
		'item_id' => 'add_assign',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Compound add-assignment should emit the plain += operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs += $rhs);')) {
		throw new RuntimeException('add_assign emitter must use plain += form.');
	}
}

function om_assert_add_assign_keyed_element_uses_dim_compound_plus_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_compound_assignment|add_assign|int_t|int.nonzero|int_t|int.zero|-|-|keyed',
		'item_id' => 'add_assign',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Compound add-assignment should emit a keyed-element += form when the lhs target kind is keyed_element.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
				'target_kind' => 'keyed_element',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, '$lhs /** vector<int> */ = [7];')) {
		throw new RuntimeException('add_assign keyed-element emitter must declare a typed vector lhs container.');
	}
	if (!str_contains($source, 'var_dump($lhs[0] += $rhs);')) {
		throw new RuntimeException('add_assign keyed-element emitter must use keyed += form.');
	}
}

function om_assert_modulo_uses_plain_percent_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_binary_arithmetic|modulo|int_t|int.nonzero|int_t|int.nonzero|-|-',
		'item_id' => 'modulo',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Modulo should emit the plain % operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs % $rhs);')) {
		throw new RuntimeException('modulo emitter must use plain % form.');
	}
}

function om_assert_bitwise_and_uses_plain_amp_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_binary_bitwise|bitwise_and|int_t|int.nonzero|int_t|int.zero|-|-',
		'item_id' => 'bitwise_and',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Bitwise and should emit the plain & operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs & $rhs);')) {
		throw new RuntimeException('bitwise_and emitter must use plain & form.');
	}
}

function om_assert_shift_left_uses_plain_double_angle_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_binary_bitwise|shift_left|int_t|int.nonzero|int_t|int.nonzero|-|-',
		'item_id' => 'shift_left',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Shift left should emit the plain << operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs << $rhs);')) {
		throw new RuntimeException('shift_left emitter must use plain << form.');
	}
}

function om_assert_shift_left_assign_uses_plain_compound_shift_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_compound_assignment|shift_left_assign|int_t|int.nonzero|int_t|int.nonzero|-|-',
		'item_id' => 'shift_left_assign',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Compound shift-left should emit the plain <<= operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs <<= $rhs);')) {
		throw new RuntimeException('shift_left_assign emitter must use plain <<= form.');
	}
}


function om_assert_logical_and_uses_plain_double_amp_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_binary_logical|logical_and|bool_t|bool.true|bool_t|bool.false|-|-',
		'item_id' => 'logical_and',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Logical and should emit the plain && operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'bool_t',
				'profile' => 'bool.true',
			],
			'rhs' => [
				'type' => 'bool_t',
				'profile' => 'bool.false',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs && $rhs);')) {
		throw new RuntimeException('logical_and emitter must use plain && form.');
	}
}

function om_assert_logical_or_uses_plain_double_pipe_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_binary_logical|logical_or|int_t|int.zero|float_t|float.nonzero|-|-',
		'item_id' => 'logical_or',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Logical or should emit the plain || operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
			'rhs' => [
				'type' => 'float_t',
				'profile' => 'float.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs || $rhs);')) {
		throw new RuntimeException('logical_or emitter must use plain || form.');
	}
}

function om_assert_equal_uses_plain_double_equals_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_comparison_equality|equal|int_t|int.zero|int_t|int.nonzero|-|-',
		'item_id' => 'equal',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Equality should emit the plain == operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs == $rhs);')) {
		throw new RuntimeException('equal emitter must use plain == form.');
	}
}

function om_assert_not_equal_uses_plain_bang_equals_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_comparison_equality|not_equal|string_t|string.empty|string_t|string.zero_string|-|-',
		'item_id' => 'not_equal',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Inequality should emit the plain != operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'string_t',
				'profile' => 'string.empty',
			],
			'rhs' => [
				'type' => 'string_t',
				'profile' => 'string.zero_string',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs != $rhs);')) {
		throw new RuntimeException('not_equal emitter must use plain != form.');
	}
}

function om_assert_identical_uses_plain_triple_equals_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_strict_identity|identical|int_t|int.zero|int_t|int.nonzero|-|-',
		'item_id' => 'identical',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Strict identity should emit the plain === operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs === $rhs);')) {
		throw new RuntimeException('identical emitter must use the plain PHP triple-equals form.');
	}
}

function om_assert_not_identical_uses_plain_bang_triple_equals_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_strict_identity|not_identical|string_t|string.empty|string_t|string.zero_string|-|-',
		'item_id' => 'not_identical',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Strict non-identity should emit the plain !== operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'string_t',
				'profile' => 'string.empty',
			],
			'rhs' => [
				'type' => 'string_t',
				'profile' => 'string.zero_string',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump(!($lhs === $rhs));')) {
		throw new RuntimeException('not_identical emitter must invert the plain PHP triple-equals form.');
	}
}

function om_assert_less_than_uses_plain_angle_bracket_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_comparison_ordering|less_than|int_t|int.zero|float_t|float.nonzero|-|-',
		'item_id' => 'less_than',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Less-than should emit the plain < operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
			'rhs' => [
				'type' => 'float_t',
				'profile' => 'float.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs < $rhs);')) {
		throw new RuntimeException('less_than emitter must use plain < form.');
	}
}

function om_assert_less_than_or_equal_uses_plain_lte_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_comparison_ordering|less_than_or_equal|float_t|float.zero|int_t|int.zero|-|-',
		'item_id' => 'less_than_or_equal',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Less-than-or-equal should emit the plain <= operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'float_t',
				'profile' => 'float.zero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs <= $rhs);')) {
		throw new RuntimeException('less_than_or_equal emitter must use plain <= form.');
	}
}

function om_assert_greater_than_uses_plain_angle_bracket_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_comparison_ordering|greater_than|float_t|float.nonzero|int_t|int.zero|-|-',
		'item_id' => 'greater_than',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Greater-than should emit the plain > operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'float_t',
				'profile' => 'float.nonzero',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs > $rhs);')) {
		throw new RuntimeException('greater_than emitter must use plain > form.');
	}
}

function om_assert_greater_than_or_equal_uses_plain_gte_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_comparison_ordering|greater_than_or_equal|int_t|int.nonzero|float_t|float.nonzero|-|-',
		'item_id' => 'greater_than_or_equal',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Greater-than-or-equal should emit the plain >= operator between lhs and rhs.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
			],
			'rhs' => [
				'type' => 'float_t',
				'profile' => 'float.nonzero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs >= $rhs);')) {
		throw new RuntimeException('greater_than_or_equal emitter must use plain >= form.');
	}
}

function om_assert_logical_not_uses_plain_bang_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_unary|logical_not|int_t|int.zero|-|-|-|-',
		'item_id' => 'logical_not',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Logical not should emit the plain unary ! operator.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump(!$lhs);')) {
		throw new RuntimeException('logical_not emitter must use plain ! form.');
	}
}

function om_assert_post_increment_uses_postfix_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_unary|post_increment|int_t|int.zero|-|-|-|-',
		'item_id' => 'post_increment',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Post increment should emit the postfix form on the operand variable.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'var_dump($lhs++);')) {
		throw new RuntimeException('post_increment emitter must use postfix ++ form.');
	}
}
