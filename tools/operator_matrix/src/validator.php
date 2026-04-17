<?php

declare(strict_types=1);

/**
 * Validate generated rows and collect all issues.
 *
 * @param array<string, mixed> $registry
 * @param list<array<string, mixed>> $rows
 * @return array<string, mixed>
 */
function om_validate_rows(array $registry, array $rows): array
{
	$errors = [];
	$warnings = [];
	$rowIds = [];
	$definitionCoverage = [];
	$allowedStatuses = [
		'supported' => true,
		'compile_time_rejected' => true,
		'unsupported_by_runtime_surface' => true,
	];
	$allowedBehaviorClasses = [
		'deterministic_value' => true,
		'throws' => true,
		'noop' => true,
		'failure_value' => true,
		'helper_routed' => true,
	];

	foreach ($rows as $index => $row) {
		$context = 'row[' . $index . '] (' . ($row['row_id'] ?? 'missing-row-id') . ')';
		$familyId = (string) ($row['family_id'] ?? '');
		$itemId = (string) ($row['item_id'] ?? '');
		$lhsType = (string) ($row['lhs_type'] ?? '');
		$rhsType = $row['rhs_type'];
		$thirdType = $row['third_type'];
		$lhsProfile = (string) ($row['lhs_profile'] ?? '');
		$rhsProfile = $row['rhs_profile'];
		$thirdProfile = $row['third_profile'];
		$status = (string) ($row['status'] ?? '');
		$behaviorClass = $row['behavior_class'] ?? null;
		$definitionKey = om_build_definition_key($familyId, $itemId, $lhsType, is_string($rhsType) ? $rhsType : null, is_string($thirdType) ? $thirdType : null);
		$coverageKey = implode('|', [
			$lhsProfile,
			is_string($rhsProfile) ? $rhsProfile : '-',
			is_string($thirdProfile) ? $thirdProfile : '-',
		]);

		if (($row['row_id'] ?? '') === '') {
			$errors[] = om_validation_issue('validation_error', $context, 'missing_row_id', 'Every row must have a row_id.');
		} elseif (isset($rowIds[$row['row_id']])) {
			$errors[] = om_validation_issue('validation_error', $context, 'duplicate_row_id', 'Duplicate row_id detected: ' . $row['row_id']);
		} else {
			$rowIds[$row['row_id']] = true;
		}

		if (!isset($registry['families_by_id'][$familyId])) {
			$errors[] = om_validation_issue('project_error', $context, 'unknown_family', 'Unknown family_id: ' . $familyId);
			continue;
		}

		$family = $registry['families_by_id'][$familyId];
		$arity = (int) $family['arity'];

		if (!isset($registry['items_by_family'][$familyId][$itemId])) {
			$errors[] = om_validation_issue('project_error', $context, 'unknown_item', 'Unknown item_id for family ' . $familyId . ': ' . $itemId);
		}

		if (!isset($registry['profiles_by_type'][$lhsType])) {
			$errors[] = om_validation_issue('validation_error', $context, 'unknown_lhs_type', 'Unknown lhs_type: ' . $lhsType);
		} else {
			$definitionCoverage[$definitionKey][$coverageKey] = true;
		}

		if (!isset($registry['known_profiles'][$lhsProfile])) {
			$errors[] = om_validation_issue('validation_error', $context, 'unknown_lhs_profile', 'Unknown lhs_profile: ' . $lhsProfile);
		}

		if ($arity >= 2) {
			if (!is_string($rhsType) || $rhsType === '') {
				$errors[] = om_validation_issue('validation_error', $context, 'missing_rhs_type', 'Binary rows must define rhs_type.');
			} elseif (!isset($registry['profiles_by_type'][$rhsType])) {
				$errors[] = om_validation_issue('validation_error', $context, 'unknown_rhs_type', 'Unknown rhs_type: ' . $rhsType);
			}

			if (!is_string($rhsProfile) || $rhsProfile === '') {
				$errors[] = om_validation_issue('validation_error', $context, 'missing_rhs_profile', 'Binary rows must define rhs_profile.');
			} elseif (!isset($registry['known_profiles'][$rhsProfile])) {
				$errors[] = om_validation_issue('validation_error', $context, 'unknown_rhs_profile', 'Unknown rhs_profile: ' . $rhsProfile);
			}
		} elseif ($rhsType !== null || $rhsProfile !== null) {
			$errors[] = om_validation_issue('validation_error', $context, 'unexpected_rhs_fields', 'Unary rows must not define rhs_type or rhs_profile.');
		}

		if ($arity >= 3) {
			if (!is_string($thirdType) || $thirdType === '') {
				$errors[] = om_validation_issue('validation_error', $context, 'missing_third_type', 'Ternary rows must define third_type.');
			}
			if (!is_string($thirdProfile) || $thirdProfile === '') {
				$errors[] = om_validation_issue('validation_error', $context, 'missing_third_profile', 'Ternary rows must define third_profile.');
			}
		} elseif ($thirdType !== null || $thirdProfile !== null) {
			$errors[] = om_validation_issue('validation_error', $context, 'unexpected_third_fields', 'Non-ternary rows must not define third_type or third_profile.');
		}

		if (!isset($allowedStatuses[$status])) {
			$errors[] = om_validation_issue('validation_error', $context, 'invalid_status', 'Invalid status: ' . $status);
		}

		if ($status === 'supported') {
			if (!is_string($behaviorClass) || $behaviorClass === '') {
				$errors[] = om_validation_issue('validation_error', $context, 'missing_behavior_class', 'Supported rows must define behavior_class.');
			} elseif (!isset($allowedBehaviorClasses[$behaviorClass])) {
				$errors[] = om_validation_issue('validation_error', $context, 'invalid_behavior_class', 'Invalid behavior_class: ' . $behaviorClass);
			}

			if (($row['result_type'] ?? null) === null || ($row['result_profile'] ?? null) === null) {
				$errors[] = om_validation_issue('validation_error', $context, 'missing_result_fields', 'Supported rows must define result_type and result_profile.');
			}
		} else {
			if ($behaviorClass !== null) {
				$warnings[] = om_validation_issue('warning', $context, 'unexpected_behavior_class', 'Non-supported row defines behavior_class; current v1 generator expects null.');
			}
		}

		if (($row['source_family_refs'] ?? []) === []) {
			$errors[] = om_validation_issue('project_error', $context, 'missing_source_family_refs', 'Every row must carry source_family_refs.');
		}

		$expectedRowId = om_build_row_id(
			$familyId,
			$itemId,
			$lhsType,
			$lhsProfile,
			is_string($rhsType) ? $rhsType : null,
			is_string($rhsProfile) ? $rhsProfile : null,
			is_string($thirdType) ? $thirdType : null,
			is_string($thirdProfile) ? $thirdProfile : null,
		);
		if (($row['row_id'] ?? '') !== $expectedRowId) {
			$errors[] = om_validation_issue('validation_error', $context, 'non_deterministic_row_id', 'row_id does not match canonical tuple-based format.');
		}
	}

	foreach ($registry['definition_keys'] as $definitionKey => $definitionMeta) {
		$expectedKeys = [];
		$lhsProfiles = $registry['profiles_by_type'][$definitionMeta['lhs_type']] ?? [];
		$rhsProfiles = ['-'];
		$thirdProfiles = ['-'];

		if (is_string($definitionMeta['rhs_type']) && $definitionMeta['rhs_type'] !== '') {
			$rhsProfiles = $registry['profiles_by_type'][$definitionMeta['rhs_type']] ?? [];
		}
		if (is_string($definitionMeta['third_type']) && $definitionMeta['third_type'] !== '') {
			$thirdProfiles = $registry['profiles_by_type'][$definitionMeta['third_type']] ?? [];
		}

		foreach ($lhsProfiles as $lhsProfile) {
			foreach ($rhsProfiles as $rhsProfile) {
				foreach ($thirdProfiles as $thirdProfile) {
					$expectedKeys[] = implode('|', [$lhsProfile, $rhsProfile, $thirdProfile]);
				}
			}
		}

		$actualKeys = array_keys($definitionCoverage[$definitionKey] ?? []);
		sort($expectedKeys);
		sort($actualKeys);

		if ($expectedKeys !== $actualKeys) {
			$missingKeys = array_values(array_diff($expectedKeys, $actualKeys));
			$extraKeys = array_values(array_diff($actualKeys, $expectedKeys));
			$messageParts = [];
			if ($missingKeys !== []) {
				$messageParts[] = 'missing combinations: ' . implode(', ', $missingKeys);
			}
			if ($extraKeys !== []) {
				$messageParts[] = 'unexpected combinations: ' . implode(', ', $extraKeys);
			}
			$errors[] = om_validation_issue(
				'validation_error',
				'definition (' . $definitionKey . ')',
				'incomplete_profile_coverage',
				'The generated row set must cover the exact known profile combinations for the definition; ' . implode('; ', $messageParts)
			);
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

/**
 * @return array<string, string>
 */
function om_validation_issue(string $severity, string $context, string $code, string $message): array
{
	return [
		'severity' => $severity,
		'context' => $context,
		'code' => $code,
		'message' => $message,
	];
}
