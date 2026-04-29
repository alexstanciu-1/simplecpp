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
	om_assert_cast_wrapper_green_slice_is_enabled_by_default();
	om_assert_ordering_wrapper_green_slice_is_enabled_by_default();
	om_assert_add_assign_uses_plain_compound_plus_form();
	om_assert_add_assign_keyed_element_uses_dim_compound_plus_form();
	om_assert_add_assign_member_property_uses_property_compound_plus_form();
	om_assert_add_assign_chained_writable_path_uses_deeper_compound_plus_form();
	om_assert_wrapper_targets_preserve_typed_leaf_properties();
	om_assert_result_or_false_empty_wrapper_targets_use_null_sentinel();
	om_assert_add_uses_plain_binary_plus_form();
	om_assert_modulo_uses_plain_percent_form();
	om_assert_bitwise_and_uses_plain_amp_form();
	om_assert_binary_bitwise_negative_runtime_rows_are_enabled_by_default();
	om_assert_binary_arithmetic_rows_are_enabled_by_default();
	om_assert_unary_negative_runtime_rows_are_enabled_by_default();
	om_assert_ordering_negative_runtime_rows_are_enabled_by_default();
	om_assert_compound_arithmetic_negative_runtime_rows_are_enabled_by_default();
	om_assert_compound_bitwise_negative_runtime_rows_are_enabled_by_default();
	om_assert_unset_value_negative_compile_rows_are_enabled_by_default();
	om_assert_unset_compile_rows_are_enabled_by_default();
	om_assert_unset_keyed_empty_hash_emits_hash_backed_seed_literal();
	om_assert_condition_truthiness_rows_are_enabled_by_default();
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

function om_assert_unset_value_negative_compile_rows_are_enabled_by_default(): void
{
	$seed = [
		'seed_id' => 'seed|language_probes_and_reset|unset_value|mixed_t|mixed.bool.false|-|-|-|-',
		'family_id' => 'language_probes_and_reset',
		'feature' => 'unset_value',
		'item_id' => 'unset_value',
		'level' => 'level_01',
		'outcome_class' => 'negative_compile',
		'test_seed_class' => 'compile_rejected',
		'operands' => [
			'lhs' => [
				'type' => 'mixed_t',
				'profile' => 'mixed.bool.false',
			],
		],
		'expected' => [
			'diagnostic_class' => 'unset_value_requires_resettable_target',
		],
	];

	if (om_should_enable_seed_by_default($seed) !== true) {
		throw new RuntimeException('unset_value compile-rejected rows must be enabled by default.');
	}

	$info = om_build_php_matrix_test_info($seed, 'unset_value_negative_compile', []);
	if (($info['expect']['generate']['success'] ?? null) !== true) {
		throw new RuntimeException('unset_value compile-rejected rows must still expect generation success.');
	}
	if (($info['expect']['compile']['success'] ?? null) !== false) {
		throw new RuntimeException('unset_value compile-rejected rows must expect compile failure.');
	}
}

function om_assert_unset_keyed_empty_hash_emits_hash_backed_seed_literal(): void
{
	$seed = [
		'seed_id' => 'seed|language_probes_and_reset|unset_keyed|mixed_t|mixed.hash.empty|-|-|-|-|keyed_element|-|-',
		'family_id' => 'language_probes_and_reset',
		'feature' => 'unset_keyed',
		'item_id' => 'unset_keyed',
		'level' => 'level_01',
		'outcome_class' => 'positive',
		'test_seed_class' => 'runtime_success',
		'operands' => [
			'lhs' => [
				'type' => 'mixed_t',
				'profile' => 'mixed.hash.empty',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, '$lhs /** mixed */ = ["k" => 1];')) {
		throw new RuntimeException('unset_keyed mixed.hash.empty rows must emit an explicit-key hash-backed seed literal so lowering keeps the keyed hash path.');
	}
}

function om_assert_condition_truthiness_rows_are_enabled_by_default(): void
{
	$positiveSeed = [
		'seed_id' => 'seed|condition_truthiness|if_condition|nullable<int_t>|nullable.present.int.nonzero|-|-|-|-',
		'family_id' => 'condition_truthiness',
		'feature' => 'if_condition',
		'item_id' => 'if_condition',
		'level' => 'level_01',
		'outcome_class' => 'positive',
		'test_seed_class' => 'runtime_success',
		'operands' => [
			'lhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.present.int.nonzero',
			],
		],
	];

	if (om_should_enable_seed_by_default($positiveSeed) !== true) {
		throw new RuntimeException('Validated condition_truthiness positive rows must be enabled by default.');
	}

	$throwSeed = [
		'seed_id' => 'seed|condition_truthiness|if_condition|mixed_t|mixed.hash.empty|-|-|-|-',
		'family_id' => 'condition_truthiness',
		'feature' => 'if_condition',
		'item_id' => 'if_condition',
		'level' => 'level_01',
		'outcome_class' => 'negative_runtime',
		'test_seed_class' => 'runtime_throw',
		'operands' => [
			'lhs' => [
				'type' => 'mixed_t',
				'profile' => 'mixed.hash.empty',
			],
		],
		'expected' => [
			'diagnostic_class' => 'invalid_condition_type',
		],
	];

	if (om_should_enable_seed_by_default($throwSeed) !== true) {
		throw new RuntimeException('Validated condition_truthiness runtime-throw rows must be enabled by default.');
	}
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

function om_assert_cast_wrapper_green_slice_is_enabled_by_default(): void
{
	$nullableIntThrow = [
		'seed_id' => 'seed|casts_explicit|cast_int|nullable<int_t>|nullable.empty|-|-|-|-',
		'family_id' => 'casts_explicit',
		'feature' => 'cast_int',
		'item_id' => 'cast_int',
		'outcome_class' => 'negative_runtime',
		'test_seed_class' => 'runtime_throw',
		'operands' => [
			'lhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.empty',
			],
			'rhs' => [
				'type' => null,
				'profile' => null,
			],
			'third' => [
				'type' => null,
				'profile' => null,
			],
		],
		'expected' => [
			'diagnostic_class' => 'invalid_nullable_unwrap_empty',
		],
	];
	if (om_should_enable_seed_by_default($nullableIntThrow) !== true) {
		throw new RuntimeException('Validated nullable<int_t> cast runtime-throw rows must be enabled by default.');
	}

	$nullableFloatPositive = $nullableIntThrow;
	$nullableFloatPositive['seed_id'] = 'seed|casts_explicit|cast_float|nullable<float_t>|nullable.present.float.nonzero|-|-|-|-';
	$nullableFloatPositive['feature'] = 'cast_float';
	$nullableFloatPositive['item_id'] = 'cast_float';
	$nullableFloatPositive['outcome_class'] = 'positive';
	$nullableFloatPositive['test_seed_class'] = 'runtime_success';
	$nullableFloatPositive['operands']['lhs']['type'] = 'nullable<float_t>';
	$nullableFloatPositive['operands']['lhs']['profile'] = 'nullable.present.float.nonzero';
	$nullableFloatPositive['expected'] = [
		'result_profile' => 'float.seven',
	];
	if (om_should_enable_seed_by_default($nullableFloatPositive) !== true) {
		throw new RuntimeException('Validated nullable<float_t> cast positive rows must be enabled by default.');
	}

	$castStringNullable = $nullableIntThrow;
	$castStringNullable['seed_id'] = 'seed|casts_explicit|cast_string|nullable<string_t>|nullable.empty|-|-|-|-';
	$castStringNullable['feature'] = 'cast_string';
	$castStringNullable['item_id'] = 'cast_string';
	$castStringNullable['outcome_class'] = 'positive';
	$castStringNullable['test_seed_class'] = 'runtime_success';
	$castStringNullable['operands']['lhs']['type'] = 'nullable<string_t>';
	$castStringNullable['operands']['lhs']['profile'] = 'nullable.empty';
	$castStringNullable['expected'] = [
		'result_profile' => 'string.empty',
	];
	if (om_should_enable_seed_by_default($castStringNullable) !== true) {
		throw new RuntimeException('Validated nullable<string_t> cast_string rows must be enabled by default.');
	}

	$nullableStringCastIntThrow = $nullableIntThrow;
	$nullableStringCastIntThrow['seed_id'] = 'seed|casts_explicit|cast_int|nullable<string_t>|nullable.present.string.bool_false_literal|-|-|-|-';
	$nullableStringCastIntThrow['feature'] = 'cast_int';
	$nullableStringCastIntThrow['item_id'] = 'cast_int';
	$nullableStringCastIntThrow['operands']['lhs']['type'] = 'nullable<string_t>';
	$nullableStringCastIntThrow['operands']['lhs']['profile'] = 'nullable.present.string.bool_false_literal';
	$nullableStringCastIntThrow['expected'] = [
		'diagnostic_class' => 'invalid_cast_int_literal',
	];
	if (om_should_enable_seed_by_default($nullableStringCastIntThrow) !== true) {
		throw new RuntimeException('Structured nullable<string_t> cast_int runtime-throw rows must now be enabled by default.');
	}
}

function om_assert_ordering_wrapper_green_slice_is_enabled_by_default(): void
{
	$seed = [
		'seed_id' => 'seed|operators_comparison_ordering|less_than|nullable<float_t>|nullable.present.float.nonzero|nullable<float_t>|nullable.present.float.nonzero|-|-',
		'family_id' => 'operators_comparison_ordering',
		'feature' => 'less_than',
		'item_id' => 'less_than',
		'level' => 'level_01',
		'outcome_class' => 'positive',
		'test_seed_class' => 'runtime_success',
		'operands' => [
			[
				'name' => 'lhs',
				'type' => 'nullable<float_t>',
				'profile' => 'nullable.present.float.nonzero',
			],
			[
				'name' => 'rhs',
				'type' => 'nullable<float_t>',
				'profile' => 'nullable.present.float.nonzero',
			],
		],
		'semantic_expectation' => [
			'status' => 'supported',
			'behavior_class' => 'deterministic_value',
			'result_type' => 'bool_t',
			'result_profile' => 'bool.false',
		],
	];

	if (om_should_enable_seed_by_default($seed) !== true) {
		throw new RuntimeException('Ordering wrapper positive rows must now be enabled by default.');
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

function om_assert_add_assign_member_property_uses_property_compound_plus_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_compound_assignment|add_assign|int_t|int.nonzero|int_t|int.zero|-|-|member',
		'item_id' => 'add_assign',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Compound add-assignment should emit a member-property += form when the lhs target kind is member_property.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
				'target_kind' => 'member_property',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'class OMMemberTarget { public int $value; }')) {
		throw new RuntimeException('add_assign member-property emitter must declare a typed holder class.');
	}
	if (!str_contains($source, '$lhs = new OMMemberTarget();')) {
		throw new RuntimeException('add_assign member-property emitter must instantiate the holder class.');
	}
	if (!str_contains($source, '$lhs->value = 7;')) {
		throw new RuntimeException('add_assign member-property emitter must seed the property value.');
	}
	if (!str_contains($source, 'var_dump($lhs->value += $rhs);')) {
		throw new RuntimeException('add_assign member-property emitter must use property += form.');
	}
}

function om_assert_add_assign_chained_writable_path_uses_deeper_compound_plus_form(): void
{
	$seed = [
		'seed_id' => 'seed|operators_compound_assignment|add_assign|int_t|int.nonzero|int_t|int.zero|-|-|chained',
		'item_id' => 'add_assign',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Compound add-assignment should emit a deeper composed write path when the lhs target kind is chained_writable_path.',
		'operands' => [
			'lhs' => [
				'type' => 'int_t',
				'profile' => 'int.nonzero',
				'target_kind' => 'chained_writable_path',
			],
			'rhs' => [
				'type' => 'int_t',
				'profile' => 'int.zero',
			],
		],
	];

	$source = om_render_php_matrix_source($seed);
	if (!str_contains($source, 'class OMChainLeaf { public int $value; }')) {
		throw new RuntimeException('add_assign chained-path emitter must declare a typed leaf class.');
	}
	if (!str_contains($source, 'class OMChainedTarget { public OMChainLeaf $slot; }')) {
		throw new RuntimeException('add_assign chained-path emitter must declare a typed outer holder class.');
	}
	if (!str_contains($source, '$lhs = new OMChainedTarget();')) {
		throw new RuntimeException('add_assign chained-path emitter must instantiate the holder class.');
	}
	if (!str_contains($source, '$lhs->slot = new OMChainLeaf();')) {
		throw new RuntimeException('add_assign chained-path emitter must instantiate the intermediate chain node.');
	}
	if (!str_contains($source, '$lhs->slot->value = 7;')) {
		throw new RuntimeException('add_assign chained-path emitter must seed the leaf property value.');
	}
	if (!str_contains($source, 'var_dump($lhs->slot->value += $rhs);')) {
		throw new RuntimeException('add_assign chained-path emitter must use the deeper chained += form.');
	}
}

function om_assert_wrapper_targets_preserve_typed_leaf_properties(): void
{
	$memberSeed = [
		'seed_id' => 'seed|operators_compound_assignment|add_assign|nullable<float_t>|nullable.present.float.nonzero|nullable<float_t>|nullable.present.float.nonzero|-|-|member',
		'item_id' => 'add_assign',
		'test_seed_class' => 'runtime_success',
		'notes' => 'Wrapper member-property targets should keep the wrapper type on the writable leaf.',
		'operands' => [
			'lhs' => [
				'type' => 'nullable<float_t>',
				'profile' => 'nullable.present.float.nonzero',
				'target_kind' => 'member_property',
			],
			'rhs' => [
				'type' => 'nullable<float_t>',
				'profile' => 'nullable.present.float.nonzero',
			],
		],
	];
	$memberSource = om_render_php_matrix_source($memberSeed);
	if (!str_contains($memberSource, 'class OMMemberTarget { public ?float $value; }')) {
		throw new RuntimeException('Wrapper member-property emitter must preserve the nullable leaf type.');
	}

	$chainSeed = $memberSeed;
	$chainSeed['seed_id'] = 'seed|operators_compound_assignment|add_assign|nullable<float_t>|nullable.present.float.nonzero|nullable<float_t>|nullable.present.float.nonzero|-|-|chain';
	$chainSeed['operands']['lhs']['target_kind'] = 'chained_writable_path';
	$chainSource = om_render_php_matrix_source($chainSeed);
	if (!str_contains($chainSource, 'class OMChainLeaf { public ?float $value; }')) {
		throw new RuntimeException('Wrapper chained-path emitter must preserve the nullable leaf type.');
	}
	if (!str_contains($chainSource, 'var_dump($lhs->slot->value += $rhs);')) {
		throw new RuntimeException('Wrapper chained-path emitter must still use the nested writable leaf.');
	}
}

function om_assert_result_or_false_empty_wrapper_targets_use_null_sentinel(): void
{
	$memberSeed = [
		'seed_id' => 'seed|operators_compound_assignment|add_assign|result_or_false<float_t>|result_or_false.sentinel.false|result_or_false<float_t>|result_or_false.success.float.nonzero|-|-|member',
		'item_id' => 'add_assign',
		'test_seed_class' => 'runtime_throw',
		'notes' => 'Empty result_or_false wrapper targets should lower through the empty sentinel form, not bool false.',
		'operands' => [
			'lhs' => [
				'type' => 'result_or_false<float_t>',
				'profile' => 'result_or_false.sentinel.false',
				'target_kind' => 'member_property',
			],
			'rhs' => [
				'type' => 'result_or_false<float_t>',
				'profile' => 'result_or_false.success.float.nonzero',
			],
		],
	];

	$memberSource = om_render_php_matrix_source($memberSeed);
	if (!str_contains($memberSource, '$lhs->value = null;')) {
		throw new RuntimeException('Empty result_or_false member-property targets must seed null, not bool false.');
	}

	$chainSeed = $memberSeed;
	$chainSeed['seed_id'] = 'seed|operators_compound_assignment|add_assign|result_or_false<float_t>|result_or_false.sentinel.false|result_or_false<float_t>|result_or_false.success.float.nonzero|-|-|chain';
	$chainSeed['operands']['lhs']['target_kind'] = 'chained_writable_path';
	$chainSource = om_render_php_matrix_source($chainSeed);
	if (!str_contains($chainSource, '$lhs->slot->value = null;')) {
		throw new RuntimeException('Empty result_or_false chained-path targets must seed null, not bool false.');
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

function om_assert_binary_bitwise_negative_runtime_rows_are_enabled_by_default(): void
{
	$seed = [
		'seed_id' => 'seed|operators_binary_bitwise|bitwise_and|nullable<int_t>|nullable.empty|nullable<int_t>|nullable.present.int.nonzero|-|-',
		'feature' => 'bitwise_and',
		'item_id' => 'bitwise_and',
		'outcome_class' => 'negative_runtime',
		'test_seed_class' => 'runtime_throw',
		'notes' => 'Binary bitwise runtime-throw rows are now active once the family is green include-disabled.',
		'operands' => [
			'lhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.empty',
			],
			'rhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.present.int.nonzero',
			],
		],
		'expected' => [
			'diagnostic_class' => 'invalid_nullable_unwrap_empty',
		],
	];

	if (om_should_enable_seed_by_default($seed) !== true) {
		throw new RuntimeException('Binary bitwise runtime-throw rows must be enabled by default once validated green.');
	}

	$shiftSeed = $seed;
	$shiftSeed['feature'] = 'shift_left';
	$shiftSeed['item_id'] = 'shift_left';
	if (om_should_enable_seed_by_default($shiftSeed) !== true) {
		throw new RuntimeException('Shift runtime-throw rows must be enabled by default once validated green.');
	}

	$controlSeed = $seed;
	$controlSeed['family_id'] = 'operators_comparison_equality';
	$controlSeed['feature'] = 'equal';
	$controlSeed['item_id'] = 'equal';
	if (om_should_enable_seed_by_default($controlSeed) !== false) {
		throw new RuntimeException('Non-enabled runtime-throw families must stay disabled by default.');
	}
}

function om_assert_binary_arithmetic_rows_are_enabled_by_default(): void
{
	$throwSeed = [
		'seed_id' => 'seed|operators_binary_arithmetic|divide|nullable<int_t>|nullable.empty|nullable<int_t>|nullable.present.int.nonzero|-|-',
		'family_id' => 'operators_binary_arithmetic',
		'feature' => 'divide',
		'item_id' => 'divide',
		'level' => 'level_01',
		'outcome_class' => 'negative_runtime',
		'test_seed_class' => 'runtime_throw',
		'operands' => [
			'lhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.empty',
			],
			'rhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.present.int.nonzero',
			],
		],
		'expected' => [
			'diagnostic_class' => 'invalid_nullable_unwrap_empty',
		],
	];
	if (om_should_enable_seed_by_default($throwSeed) !== true) {
		throw new RuntimeException('Binary arithmetic runtime-throw rows must now be enabled by default.');
	}

	$positiveSeed = $throwSeed;
	$positiveSeed['seed_id'] = 'seed|operators_binary_arithmetic|add|nullable<float_t>|nullable.present.float.nonzero|nullable<float_t>|nullable.present.float.nonzero|-|-';
	$positiveSeed['feature'] = 'add';
	$positiveSeed['item_id'] = 'add';
	$positiveSeed['outcome_class'] = 'positive';
	$positiveSeed['test_seed_class'] = 'runtime_success';
	$positiveSeed['operands']['lhs']['type'] = 'nullable<float_t>';
	$positiveSeed['operands']['lhs']['profile'] = 'nullable.present.float.nonzero';
	$positiveSeed['operands']['rhs']['type'] = 'nullable<float_t>';
	$positiveSeed['operands']['rhs']['profile'] = 'nullable.present.float.nonzero';
	unset($positiveSeed['expected']);
	if (om_should_enable_seed_by_default($positiveSeed) !== true) {
		throw new RuntimeException('Binary arithmetic wrapper-success rows must now be enabled by default.');
	}
}

function om_assert_unary_negative_runtime_rows_are_enabled_by_default(): void
{
	$logicalNotThrow = [
		'seed_id' => 'seed|operators_unary|logical_not|hash_t|hash.empty|-|-|-|-',
		'family_id' => 'operators_unary',
		'feature' => 'logical_not',
		'item_id' => 'logical_not',
		'outcome_class' => 'negative_runtime',
		'test_seed_class' => 'runtime_throw',
		'operands' => [
			'lhs' => [
				'type' => 'hash_t',
				'profile' => 'hash.empty',
			],
			'rhs' => [
				'type' => null,
				'profile' => null,
			],
			'third' => [
				'type' => null,
				'profile' => null,
			],
		],
		'expected' => [
			'diagnostic_class' => 'invalid_condition_type',
		],
	];
	if (om_should_enable_seed_by_default($logicalNotThrow) !== true) {
		throw new RuntimeException('Validated logical_not runtime-throw rows must be enabled by default.');
	}

	$postIncrementThrow = $logicalNotThrow;
	$postIncrementThrow['seed_id'] = 'seed|operators_unary|post_increment|mixed_t|mixed.null|-|-|-|-';
	$postIncrementThrow['feature'] = 'post_increment';
	$postIncrementThrow['item_id'] = 'post_increment';
	$postIncrementThrow['expected'] = [
		'diagnostic_class' => 'unsupported_increment_operand',
	];
	$postIncrementThrow['operands']['lhs']['type'] = 'mixed_t';
	$postIncrementThrow['operands']['lhs']['profile'] = 'mixed.null';
	if (om_should_enable_seed_by_default($postIncrementThrow) !== true) {
		throw new RuntimeException('Validated post_increment runtime-throw rows must be enabled by default.');
	}

	$bitwiseNotThrow = $logicalNotThrow;
	$bitwiseNotThrow['seed_id'] = 'seed|operators_unary|bitwise_not|mixed_t|mixed.string.bool_true_literal|-|-|-|-';
	$bitwiseNotThrow['feature'] = 'bitwise_not';
	$bitwiseNotThrow['item_id'] = 'bitwise_not';
	$bitwiseNotThrow['expected'] = [
		'diagnostic_class' => 'invalid_unary_bitwise_operand',
	];
	$bitwiseNotThrow['operands']['lhs']['type'] = 'mixed_t';
	$bitwiseNotThrow['operands']['lhs']['profile'] = 'mixed.string.bool_true_literal';
	if (om_should_enable_seed_by_default($bitwiseNotThrow) !== true) {
		throw new RuntimeException('Validated bitwise_not mixed-string runtime-throw rows must now be enabled by default.');
	}
}

function om_assert_ordering_negative_runtime_rows_are_enabled_by_default(): void
{
	$seed = [
		'seed_id' => 'seed|operators_comparison_ordering|less_than_or_equal|nullable<int_t>|nullable.empty|nullable<int_t>|nullable.empty|-|-',
		'family_id' => 'operators_comparison_ordering',
		'feature' => 'less_than_or_equal',
		'item_id' => 'less_than_or_equal',
		'level' => 'level_01',
		'outcome_class' => 'negative_runtime',
		'test_seed_class' => 'runtime_throw',
		'operands' => [
			[
				'name' => 'lhs',
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.empty',
			],
			[
				'name' => 'rhs',
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.empty',
			],
		],
		'semantic_expectation' => [
			'status' => 'supported',
			'behavior_class' => 'throws',
			'result_type' => 'bool_t',
			'result_profile' => 'bool.false',
		],
	];

	$info = om_build_php_matrix_test_info($seed, 'ordering_negative_runtime', []);
	if (($info['enabled'] ?? null) !== true) {
		throw new RuntimeException('Ordering negative-runtime rows must now be enabled by default.');
	}
}

function om_assert_compound_bitwise_negative_runtime_rows_are_enabled_by_default(): void
{
	$seed = [
		'seed_id' => 'seed|operators_compound_assignment|bitwise_and_assign|nullable<int_t>|nullable.empty|nullable<int_t>|nullable.present.int.nonzero|-|-|assignable_variable|-|-',
		'feature' => 'bitwise_and_assign',
		'item_id' => 'bitwise_and_assign',
		'outcome_class' => 'negative_runtime',
		'test_seed_class' => 'runtime_throw',
		'notes' => 'Compound bitwise assignment runtime-throw rows are active after include-disabled validation.',
		'operands' => [
			'lhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.empty',
				'target_kind' => 'assignable_variable',
			],
			'rhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.present.int.nonzero',
			],
		],
		'expected' => [
			'diagnostic_class' => 'invalid_nullable_unwrap_empty',
		],
	];

	if (om_should_enable_seed_by_default($seed) !== true) {
		throw new RuntimeException('Compound bitwise assignment runtime-throw rows must be enabled by default once validated green.');
	}

	$shiftSeed = $seed;
	$shiftSeed['feature'] = 'shift_left_assign';
	$shiftSeed['item_id'] = 'shift_left_assign';
	if (om_should_enable_seed_by_default($shiftSeed) !== true) {
		throw new RuntimeException('Compound shift assignment runtime-throw rows must be enabled by default once validated green.');
	}

	$controlSeed = $seed;
	$controlSeed['feature'] = 'logical_and';
	$controlSeed['item_id'] = 'logical_and';
	if (om_should_enable_seed_by_default($controlSeed) !== true) {
		throw new RuntimeException('Validated logical runtime-throw rows must now be enabled by default.');
	}
}

function om_assert_compound_arithmetic_negative_runtime_rows_are_enabled_by_default(): void
{
	$seed = [
		'seed_id' => 'seed|operators_compound_assignment|subtract_assign|nullable<int_t>|nullable.empty|nullable<int_t>|nullable.present.int.nonzero|-|-|assignable_variable|-|-',
		'feature' => 'subtract_assign',
		'item_id' => 'subtract_assign',
		'outcome_class' => 'negative_runtime',
		'test_seed_class' => 'runtime_throw',
		'notes' => 'Validated arithmetic compound runtime-throw rows are active once the whole feature slice is green include-disabled.',
		'operands' => [
			'lhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.empty',
				'target_kind' => 'assignable_variable',
			],
			'rhs' => [
				'type' => 'nullable<int_t>',
				'profile' => 'nullable.present.int.nonzero',
			],
		],
		'expected' => [
			'diagnostic_class' => 'invalid_nullable_unwrap_empty',
		],
	];

	if (om_should_enable_seed_by_default($seed) !== true) {
		throw new RuntimeException('Compound subtract assignment runtime-throw rows must be enabled by default once validated green.');
	}

	$multiplySeed = $seed;
	$multiplySeed['feature'] = 'multiply_assign';
	$multiplySeed['item_id'] = 'multiply_assign';
	if (om_should_enable_seed_by_default($multiplySeed) !== true) {
		throw new RuntimeException('Compound multiply assignment runtime-throw rows must be enabled by default once validated green.');
	}

	$addSeed = $seed;
	$addSeed['feature'] = 'add_assign';
	$addSeed['item_id'] = 'add_assign';
	if (om_should_enable_seed_by_default($addSeed) !== true) {
		throw new RuntimeException('Compound add assignment runtime-throw rows must be enabled by default once validated green.');
	}

	$divideSeed = $seed;
	$divideSeed['feature'] = 'divide_assign';
	$divideSeed['item_id'] = 'divide_assign';
	if (om_should_enable_seed_by_default($divideSeed) !== true) {
		throw new RuntimeException('Compound divide assignment runtime-throw rows must be enabled by default once validated green.');
	}

	$moduloSeed = $seed;
	$moduloSeed['feature'] = 'modulo_assign';
	$moduloSeed['item_id'] = 'modulo_assign';
	if (om_should_enable_seed_by_default($moduloSeed) !== true) {
		throw new RuntimeException('Compound modulo assignment runtime-throw rows must be enabled by default once validated green.');
	}
}

function om_assert_unset_compile_rows_are_enabled_by_default(): void
{
	$unsetValueSeed = [
		'seed_id' => 'seed|language_probes_and_reset|unset_value|mixed_t|mixed.null|-|-|-|-',
		'family_id' => 'language_probes_and_reset',
		'feature' => 'unset_value',
		'item_id' => 'unset_value',
		'outcome_class' => 'negative_compile',
		'test_seed_class' => 'compile_fail',
		'operands' => [
			'lhs' => [
				'type' => 'mixed_t',
				'profile' => 'mixed.null',
			],
			'rhs' => [
				'type' => null,
				'profile' => null,
			],
			'third' => [
				'type' => null,
				'profile' => null,
			],
		],
		'expected' => [
			'diagnostic_class' => 'unsupported_unset_target',
		],
	];
	if (om_should_enable_seed_by_default($unsetValueSeed) !== true) {
		throw new RuntimeException('unset_value compile-rejected rows must stay enabled by default.');
	}

	$unsetKeyedSeed = $unsetValueSeed;
	$unsetKeyedSeed['seed_id'] = 'seed|language_probes_and_reset|unset_keyed|mixed_t|mixed.bool.false|-|-|-|-';
	$unsetKeyedSeed['feature'] = 'unset_keyed';
	$unsetKeyedSeed['item_id'] = 'unset_keyed';
	$unsetKeyedSeed['expected'] = [
		'diagnostic_class' => 'unsupported_unset_keyed_target',
	];
	$unsetKeyedSeed['operands']['lhs']['profile'] = 'mixed.bool.false';
	$unsetKeyedSeed['operands']['lhs']['target_kind'] = 'keyed_element';
	if (om_should_enable_seed_by_default($unsetKeyedSeed) !== true) {
		throw new RuntimeException('unset_keyed compile-rejected rows must now be enabled by default.');
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
