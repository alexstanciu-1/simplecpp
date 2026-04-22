<?php

declare(strict_types=1);

/**
 * Build deterministic test-seed rows from canonical matrix rows.
 *
 * The seed layer is intentionally row-faithful in v1.
 * Each matrix row with a non-empty test_seed_class produces exactly one seed.
 * Later emitters may deduplicate or collapse seeds, but that is out of scope here.
 *
 * @param array<string, mixed> $registry
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function om_build_test_seeds(array $registry, array $rows): array
{
	om_assert_seed_builder_routes_are_valid();

	$seeds = [];
	foreach ($rows as $row) {
		$testSeedClass = $row['test_seed_class'] ?? null;
		if (!is_string($testSeedClass) || $testSeedClass === '') {
			continue;
		}
		if (!om_has_seed_builder_route_for_family((string) ($row['family_id'] ?? ''))) {
			continue;
		}

		$route = om_resolve_seed_builder_route($row);
		$builder = $route['builder'];
		$builtSeeds = $builder($registry, $row, $route);
		foreach ($builtSeeds as $seed) {
			$seeds[] = $seed;
		}
	}

	usort($seeds, static function (array $left, array $right): int {
		return [
			$left['suite'],
			$left['feature'],
			$left['group'],
			$left['suggested_stem'],
			$left['seed_id'],
		] <=> [
			$right['suite'],
			$right['feature'],
			$right['group'],
			$right['suggested_stem'],
			$right['seed_id'],
		];
	});

	return $seeds;
}

/**
 * @return list<array<string, mixed>>
 */
function om_seed_builder_routes(): array
{
	return [
		[
			'family_id' => 'casts_explicit',
			'suite' => 'php-matrix',
			'target_flow' => 'php',
			'level' => 'level_01',
			'builder' => 'om_build_unary_bool_result_seed',
		],
		[
			'family_id' => 'condition_truthiness',
			'suite' => 'php-matrix',
			'target_flow' => 'php',
			'level' => 'level_01',
			'builder' => 'om_build_unary_bool_result_seed',
		],
		[
			'family_id' => 'operators_conditional_selection',
			'suite' => 'php-matrix',
			'target_flow' => 'php',
			'level' => 'level_01',
			'builder' => 'om_build_conditional_selection_seed',
		],
	];
}

function om_assert_seed_builder_routes_are_valid(): void
{
	$seenFamilies = [];
	foreach (om_seed_builder_routes() as $routeIndex => $route) {
		$familyId = (string) ($route['family_id'] ?? '');
		if ($familyId === '') {
			throw new RuntimeException('Seed builder route is missing family_id at index ' . $routeIndex);
		}
		if (isset($seenFamilies[$familyId])) {
			throw new RuntimeException('Multiple seed builder routes declared for family_id: ' . $familyId);
		}

		$builder = $route['builder'] ?? null;
		if (!is_string($builder) || $builder === '' || !function_exists($builder)) {
			throw new RuntimeException('Seed builder route for ' . $familyId . ' references an unknown builder.');
		}

		$suite = $route['suite'] ?? null;
		if (!is_string($suite) || $suite === '') {
			throw new RuntimeException('Seed builder route for ' . $familyId . ' is missing suite.');
		}

		$targetFlow = $route['target_flow'] ?? null;
		if (!is_string($targetFlow) || $targetFlow === '') {
			throw new RuntimeException('Seed builder route for ' . $familyId . ' is missing target_flow.');
		}

		$seenFamilies[$familyId] = true;
	}
}

function om_has_seed_builder_route_for_family(string $familyId): bool
{
	foreach (om_seed_builder_routes() as $route) {
		if (($route['family_id'] ?? null) === $familyId) {
			return true;
		}
	}

	return false;
}

/**
 * @param list<array<string, mixed>> $rows
 */
function om_validate_seed_builder_routing(array $rows): void
{
	om_assert_seed_builder_routes_are_valid();

	foreach ($rows as $index => $row) {
		$testSeedClass = $row['test_seed_class'] ?? null;
		if (!is_string($testSeedClass) || $testSeedClass === '') {
			continue;
		}
		if (!om_has_seed_builder_route_for_family((string) ($row['family_id'] ?? ''))) {
			continue;
		}

		try {
			om_resolve_seed_builder_route($row);
		} catch (RuntimeException $exception) {
			$rowId = (string) ($row['row_id'] ?? 'missing-row-id');
			throw new RuntimeException(
				'Seed builder routing failed for row[' . $index . '] (' . $rowId . '): ' . $exception->getMessage(),
				0,
				$exception
			);
		}
	}
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function om_resolve_seed_builder_route(array $row): array
{
	$familyId = (string) ($row['family_id'] ?? '');
	foreach (om_seed_builder_routes() as $route) {
		if (($route['family_id'] ?? null) === $familyId) {
			return $route;
		}
	}

	throw new RuntimeException('No seed builder route defined for family_id: ' . $familyId);
}

/**
 * @param array<string, mixed> $registry
 * @param array<string, mixed> $row
 * @param array<string, mixed> $route
 * @return list<array<string, mixed>>
 */
function om_build_conditional_selection_seed(array $registry, array $row, array $route): array
{
	$seedId = om_build_test_seed_id($row);
	$pathMeta = om_build_test_seed_paths($row, $route);

	return [[
		'seed_id' => $seedId,
		'family_id' => $row['family_id'],
		'item_id' => $row['item_id'],
		'subfamily_id' => $row['subfamily_id'],
		'arity' => $row['arity'],
		'test_seed_class' => $row['test_seed_class'],
		'matrix_status' => $row['status'],
		'behavior_class' => $row['behavior_class'],
		'outcome_class' => om_resolve_seed_outcome_class($row),
		'suite' => $route['suite'],
		'target_flow' => $route['target_flow'],
		'level' => $route['level'],
		'feature' => (string) $row['item_id'],
		'group' => (string) $row['item_id'],
		'relative_directory' => $pathMeta['relative_directory'],
		'suggested_stem' => $pathMeta['suggested_stem'],
		'suggested_source_path' => $pathMeta['suggested_source_path'],
		'suggested_info_path' => $pathMeta['suggested_info_path'],
		'operands' => [
			'lhs' => [
				'type' => $row['lhs_type'],
				'profile' => $row['lhs_profile'],
				'target_kind' => $row['lhs_target_kind'],
			],
			'rhs' => [
				'type' => $row['rhs_type'],
				'profile' => $row['rhs_profile'],
				'target_kind' => $row['rhs_target_kind'],
			],
			'third' => [
				'type' => $row['third_type'],
				'profile' => $row['third_profile'],
				'target_kind' => $row['third_target_kind'],
			],
		],
		'expected' => [
			'result_type' => $row['result_type'],
			'result_profile' => $row['result_profile'],
			'diagnostic_class' => $row['diagnostic_class'],
		],
		'edge_case' => $row['edge_case'],
		'edge_case_id' => $row['edge_case_id'],
		'source_row_ids' => [$row['row_id']],
		'source_family_refs' => $row['source_family_refs'],
		'source_item_refs' => $row['source_item_refs'],
		'notes' => $row['notes'],
	]];
}

/**
 * @param array<string, mixed> $registry
 * @param array<string, mixed> $row
 * @param array<string, mixed> $route
 * @return list<array<string, mixed>>
 */
function om_build_unary_bool_result_seed(array $registry, array $row, array $route): array
{
	$seedId = om_build_test_seed_id($row);
	$pathMeta = om_build_test_seed_paths($row, $route);

	return [[
		'seed_id' => $seedId,
		'family_id' => $row['family_id'],
		'item_id' => $row['item_id'],
		'subfamily_id' => $row['subfamily_id'],
		'arity' => $row['arity'],
		'test_seed_class' => $row['test_seed_class'],
		'matrix_status' => $row['status'],
		'behavior_class' => $row['behavior_class'],
		'outcome_class' => om_resolve_seed_outcome_class($row),
		'suite' => $route['suite'],
		'target_flow' => $route['target_flow'],
		'level' => $route['level'],
		'feature' => (string) $row['item_id'],
		'group' => (string) $row['item_id'],
		'relative_directory' => $pathMeta['relative_directory'],
		'suggested_stem' => $pathMeta['suggested_stem'],
		'suggested_source_path' => $pathMeta['suggested_source_path'],
		'suggested_info_path' => $pathMeta['suggested_info_path'],
		'operands' => [
			'lhs' => [
				'type' => $row['lhs_type'],
				'profile' => $row['lhs_profile'],
				'target_kind' => $row['lhs_target_kind'],
			],
			'rhs' => [
				'type' => $row['rhs_type'],
				'profile' => $row['rhs_profile'],
				'target_kind' => $row['rhs_target_kind'],
			],
			'third' => [
				'type' => $row['third_type'],
				'profile' => $row['third_profile'],
				'target_kind' => $row['third_target_kind'],
			],
		],
		'expected' => [
			'result_type' => $row['result_type'],
			'result_profile' => $row['result_profile'],
			'diagnostic_class' => $row['diagnostic_class'],
		],
		'edge_case' => $row['edge_case'],
		'edge_case_id' => $row['edge_case_id'],
		'source_row_ids' => [$row['row_id']],
		'source_family_refs' => $row['source_family_refs'],
		'source_item_refs' => $row['source_item_refs'],
		'notes' => $row['notes'],
	]];
}

/**
 * @param array<string, mixed> $row
 */
function om_build_test_seed_id(array $row): string
{
	return 'seed|' . $row['row_id'];
}

/**
 * @param array<string, mixed> $row
 * @param array<string, mixed> $route
 * @return array<string, string>
 */
function om_build_test_seed_paths(array $row, array $route): array
{
	$suite = (string) $route['suite'];
	$level = (string) ($route['level'] ?? 'level_01');
	$directory = 'tests/' . $suite . '/' . $row['item_id'] . '/' . $level;
	$stemParts = [
		(string) $row['item_id'],
		om_slugify_seed_component((string) $row['lhs_type']),
		om_slugify_seed_component((string) $row['lhs_profile']),
	];

	if (is_string($row['rhs_type']) && $row['rhs_type'] !== '') {
		$stemParts[] = om_slugify_seed_component($row['rhs_type']);
	}
	if (is_string($row['rhs_profile']) && $row['rhs_profile'] !== '') {
		$stemParts[] = om_slugify_seed_component($row['rhs_profile']);
	}
	if (is_string($row['third_type']) && $row['third_type'] !== '') {
		$stemParts[] = om_slugify_seed_component($row['third_type']);
	}
	if (is_string($row['third_profile']) && $row['third_profile'] !== '') {
		$stemParts[] = om_slugify_seed_component($row['third_profile']);
	}
	$stemParts[] = om_slugify_seed_component((string) $row['status']);
	if (is_string($row['behavior_class']) && $row['behavior_class'] !== '') {
		$stemParts[] = om_slugify_seed_component($row['behavior_class']);
	}
	if (is_string($row['diagnostic_class']) && $row['diagnostic_class'] !== '') {
		$stemParts[] = om_slugify_seed_component($row['diagnostic_class']);
	}

	$stem = implode('__', $stemParts);

	return [
		'relative_directory' => $directory,
		'suggested_stem' => $stem,
		'suggested_source_path' => $directory . '/' . $stem . '.php',
		'suggested_info_path' => $directory . '/' . $stem . '.test-info.json',
	];
}

/**
 * @param array<string, mixed> $row
 */
function om_resolve_seed_outcome_class(array $row): string
{
	$status = (string) ($row['status'] ?? '');
	$behaviorClass = $row['behavior_class'] ?? null;
	$diagnosticClass = (string) ($row['diagnostic_class'] ?? '');

	if ($diagnosticClass === 'coalesce_reject_result_or_bool') {
		return 'negative_runtime';
	}
	if ($status === 'compile_time_rejected') {
		return 'negative_generate';
	}
	if ($status === 'unsupported_by_runtime_surface') {
		return 'negative_generate';
	}
	if ($status === 'supported' && $behaviorClass === 'throws') {
		return 'negative_runtime';
	}

	return 'positive';
}

function om_slugify_seed_component(string $value): string
{
	$value = strtolower($value);
	$value = str_replace(['<', '>', '(', ')', '|'], ['_', '_', '_', '_', '_'], $value);
	$value = str_replace([':', '/', '\\', ',', ' ', '?'], ['_', '_', '_', '_', '_', '_'], $value);
	$value = preg_replace('/[^a-z0-9._-]+/', '_', $value) ?? $value;
	$value = preg_replace('/_+/', '_', $value) ?? $value;
	return trim($value, '_.-');
}
