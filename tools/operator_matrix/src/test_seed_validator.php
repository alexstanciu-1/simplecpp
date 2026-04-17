<?php

declare(strict_types=1);

/**
 * Validate generated test seeds.
 *
 * @param list<array<string, mixed>> $rows
 * @param list<array<string, mixed>> $seeds
 * @return array<string, mixed>
 */
function om_validate_test_seeds(array $rows, array $seeds): array
{
	$errors = [];
	$warnings = [];
	$seedIds = [];
	$rowToSeed = [];
	$rowsRequiringSeeds = [];

	foreach ($rows as $row) {
		if (($row['family_id'] ?? null) !== 'operators_conditional_selection') {
			continue;
		}

		$testSeedClass = $row['test_seed_class'] ?? null;
		if (is_string($testSeedClass) && $testSeedClass !== '') {
			$rowsRequiringSeeds[(string) $row['row_id']] = true;
		}
	}

	foreach ($seeds as $index => $seed) {
		$context = 'seed[' . $index . '] (' . ($seed['seed_id'] ?? 'missing-seed-id') . ')';
		$seedId = (string) ($seed['seed_id'] ?? '');
		if ($seedId === '') {
			$errors[] = om_validation_issue('validation_error', $context, 'missing_seed_id', 'Every seed must have a seed_id.');
		} elseif (isset($seedIds[$seedId])) {
			$errors[] = om_validation_issue('validation_error', $context, 'duplicate_seed_id', 'Duplicate seed_id detected: ' . $seedId);
		} else {
			$seedIds[$seedId] = true;
		}

		$sourceRowIds = $seed['source_row_ids'] ?? null;
		if (!is_array($sourceRowIds) || $sourceRowIds === []) {
			$errors[] = om_validation_issue('validation_error', $context, 'missing_source_row_ids', 'Every seed must reference at least one source row id.');
			continue;
		}

		foreach ($sourceRowIds as $rowId) {
			if (!is_string($rowId) || $rowId === '') {
				$errors[] = om_validation_issue('validation_error', $context, 'invalid_source_row_id', 'Seed source_row_ids must contain only non-empty strings.');
				continue;
			}
			$rowToSeed[$rowId] = true;
			if (!isset($rowsRequiringSeeds[$rowId])) {
				$warnings[] = om_validation_issue('warning', $context, 'unexpected_source_row_id', 'Seed references a row that does not currently require test generation: ' . $rowId);
			}
		}
	}

	foreach (array_keys($rowsRequiringSeeds) as $rowId) {
		if (!isset($rowToSeed[$rowId])) {
			$errors[] = om_validation_issue('validation_error', 'rows', 'missing_seed_for_row', 'No test seed was generated for row_id: ' . $rowId);
		}
	}

	return [
		'ok' => $errors === [],
		'error_count' => count($errors),
		'warning_count' => count($warnings),
		'errors' => $errors,
		'warnings' => $warnings,
	];
}
