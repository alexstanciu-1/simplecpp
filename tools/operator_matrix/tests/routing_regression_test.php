<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/filesystem.php';
require_once dirname(__DIR__) . '/src/test_seed_builder.php';
require_once dirname(__DIR__) . '/src/test_emitter.php';

om_run_routing_regression_test_suite();
echo "routing_regression_test: ok\n";

function om_run_routing_regression_test_suite(): void
{
	om_assert_seed_builder_routes_are_valid();
	om_assert_emitter_routes_are_valid(om_emitter_routes());

	$seedRoutes = om_seed_builder_routes();
	if (count($seedRoutes) !== 3) {
		throw new RuntimeException('Expected exactly three active seed builder routes.');
	}

	$seedFamilies = [];
	foreach ($seedRoutes as $seedRoute) {
		$seedFamilies[(string) ($seedRoute['family_id'] ?? '')] = [
			'suite' => $seedRoute['suite'] ?? null,
			'target_flow' => $seedRoute['target_flow'] ?? null,
			'level' => $seedRoute['level'] ?? null,
		];
	}
	ksort($seedFamilies);
	om_assert_same([
		'casts_explicit' => [
			'suite' => 'php-matrix',
			'target_flow' => 'php',
			'level' => 'level_01',
		],
		'condition_truthiness' => [
			'suite' => 'php-matrix',
			'target_flow' => 'php',
			'level' => 'level_01',
		],
		'operators_conditional_selection' => [
			'suite' => 'php-matrix',
			'target_flow' => 'php',
			'level' => 'level_01',
		],
	], $seedFamilies, 'Unexpected active seed builder families.');

	om_assert_true(om_has_seed_builder_route_for_family('casts_explicit'), 'casts_explicit must be routable.');
	om_assert_true(om_has_seed_builder_route_for_family('condition_truthiness'), 'Condition truthiness must be routable.');
	om_assert_true(om_has_seed_builder_route_for_family('operators_conditional_selection'), 'Active family must be routable.');

	$resolvedCastRoute = om_resolve_seed_builder_route([
		'family_id' => 'casts_explicit',
	]);
	om_assert_same('casts_explicit', $resolvedCastRoute['family_id'] ?? null, 'Resolved seed route must match casts_explicit.');

	$resolvedConditionRoute = om_resolve_seed_builder_route([
		'family_id' => 'condition_truthiness',
	]);
	om_assert_same('condition_truthiness', $resolvedConditionRoute['family_id'] ?? null, 'Resolved seed route must match condition_truthiness.');

	$resolvedSeedRoute = om_resolve_seed_builder_route([
		'family_id' => 'operators_conditional_selection',
	]);
	om_assert_same('operators_conditional_selection', $resolvedSeedRoute['family_id'] ?? null, 'Resolved seed route must match active family.');

	om_expect_runtime_exception(
		static function (): void {
			om_resolve_seed_builder_route([
				'family_id' => 'future_family',
			]);
		},
		'No seed builder route defined for family_id: future_family'
	);

	$emitterRoutes = om_emitter_routes();
	if (count($emitterRoutes) !== 2) {
		throw new RuntimeException('Expected exactly two emitter routes.');
	}

	$emitterSuites = [];
	foreach ($emitterRoutes as $route) {
		$emitterSuites[(string) ($route['suite'] ?? '')] = (string) ($route['target_flow'] ?? '');
	}
	ksort($emitterSuites);
	om_assert_same(
		[
			'php-matrix' => 'php',
			'runtime-matrix' => 'runtime',
		],
		$emitterSuites,
		'Unexpected emitter suite map.'
	);

	$resolvedPhpEmitter = om_resolve_emitter_route([
		'suite' => 'php-matrix',
		'target_flow' => 'php',
	], $emitterRoutes);
	om_assert_same('php-matrix', $resolvedPhpEmitter['suite'] ?? null, 'Resolved emitter route must match php-matrix.');

	$resolvedRuntimeEmitter = om_resolve_emitter_route([
		'suite' => 'runtime-matrix',
		'target_flow' => 'runtime',
	], $emitterRoutes);
	om_assert_same('runtime-matrix', $resolvedRuntimeEmitter['suite'] ?? null, 'Resolved emitter route must match runtime-matrix.');

	om_expect_runtime_exception(
		static function () use ($emitterRoutes): void {
			om_resolve_emitter_route([
				'suite' => 'php-matrix',
				'target_flow' => 'runtime',
			], $emitterRoutes);
		},
		'Emitter route target_flow mismatch for suite php-matrix'
	);

	om_expect_runtime_exception(
		static function () use ($emitterRoutes): void {
			om_resolve_emitter_route([
				'suite' => 'unknown-suite',
				'target_flow' => 'php',
			], $emitterRoutes);
		},
		'No emitter route defined for suite: unknown-suite'
	);
}

function om_assert_same(mixed $expected, mixed $actual, string $message): void
{
	if ($expected !== $actual) {
		throw new RuntimeException($message . ' expected=' . json_encode($expected, JSON_UNESCAPED_SLASHES) . ' actual=' . json_encode($actual, JSON_UNESCAPED_SLASHES));
	}
}

function om_assert_true(bool $value, string $message): void
{
	if ($value !== true) {
		throw new RuntimeException($message);
	}
}

function om_assert_false(bool $value, string $message): void
{
	if ($value !== false) {
		throw new RuntimeException($message);
	}
}

function om_expect_runtime_exception(callable $callback, string $messageFragment): void
{
	try {
		$callback();
	} catch (RuntimeException $exception) {
		if (str_contains($exception->getMessage(), $messageFragment)) {
			return;
		}

		throw new RuntimeException(
			'Expected RuntimeException containing "' . $messageFragment . '", got: ' . $exception->getMessage(),
			0,
			$exception
		);
	}

	throw new RuntimeException('Expected RuntimeException containing "' . $messageFragment . '".');
}
