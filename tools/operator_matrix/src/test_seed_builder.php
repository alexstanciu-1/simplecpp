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
	$seeds = [];

	foreach ($rows as $row) {
		if (($row['family_id'] ?? null) !== 'operators_conditional_selection') {
			continue;
		}

		$testSeedClass = $row['test_seed_class'] ?? null;
		if (!is_string($testSeedClass) || $testSeedClass === '') {
			continue;
		}

		$seedId = om_build_test_seed_id($row);
		$pathMeta = om_build_test_seed_paths($row);
		$seeds[] = [
			'seed_id' => $seedId,
			'family_id' => $row['family_id'],
			'item_id' => $row['item_id'],
			'subfamily_id' => $row['subfamily_id'],
			'arity' => $row['arity'],
			'test_seed_class' => $testSeedClass,
			'matrix_status' => $row['status'],
			'behavior_class' => $row['behavior_class'],
			'outcome_class' => om_resolve_seed_outcome_class($row),
			'suite' => om_resolve_seed_suite($row),
			'target_flow' => om_resolve_seed_target_flow($row),
			'level' => 'level_01',
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
		];
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
 * @param array<string, mixed> $row
 */
function om_build_test_seed_id(array $row): string
{
	return 'seed|' . $row['row_id'];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, string>
 */
function om_build_test_seed_paths(array $row): array
{
	$suite = om_resolve_seed_suite($row);
	$directory = 'tests/' . $suite . '/' . $row['item_id'] . '/level_01';
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
function om_resolve_seed_suite(array $row): string
{
	return match ((string) $row['family_id']) {
		'operators_conditional_selection' => 'php-matrix',
		default => 'php-matrix',
	};
}

/**
 * @param array<string, mixed> $row
 */
function om_resolve_seed_target_flow(array $row): string
{
	return match (om_resolve_seed_suite($row)) {
		'runtime-matrix' => 'runtime',
		default => 'php',
	};
}

/**
 * @param array<string, mixed> $row
 */
function om_resolve_seed_outcome_class(array $row): string
{
	$status = (string) ($row['status'] ?? '');
	$behaviorClass = $row['behavior_class'] ?? null;

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
