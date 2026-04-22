<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/test_emitter.php';

om_run_emitter_regression_test_suite();

function om_run_emitter_regression_test_suite(): void
{
	om_assert_if_condition_emits_statement_form();
	om_assert_cast_bool_uses_explicit_cast_form();
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
