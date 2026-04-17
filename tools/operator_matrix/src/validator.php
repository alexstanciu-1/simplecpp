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
	$definitionProfiles = [];
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
		$lhsProfile = (string) ($row['lhs_profile'] ?? '');
		$status = (string) ($row['status'] ?? '');
		$behaviorClass = $row['behavior_class'] ?? null;
		$definitionKey = implode('|', [$familyId, $itemId, $lhsType]);

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

		if (!isset($registry['items_by_family'][$familyId][$itemId])) {
			$errors[] = om_validation_issue('project_error', $context, 'unknown_item', 'Unknown item_id for family ' . $familyId . ': ' . $itemId);
		}

		if (!isset($registry['profiles_by_type'][$lhsType])) {
			$errors[] = om_validation_issue('validation_error', $context, 'unknown_lhs_type', 'Unknown lhs_type: ' . $lhsType);
		} else {
			$definitionProfiles[$definitionKey][$lhsProfile] = true;
		}

		if (!isset($registry['known_profiles'][$lhsProfile])) {
			$errors[] = om_validation_issue('validation_error', $context, 'unknown_lhs_profile', 'Unknown lhs_profile: ' . $lhsProfile);
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

		$expectedRowId = om_build_row_id($familyId, $itemId, $lhsType, $lhsProfile);
		if (($row['row_id'] ?? '') !== $expectedRowId) {
			$errors[] = om_validation_issue('validation_error', $context, 'non_deterministic_row_id', 'row_id does not match canonical tuple-based format.');
		}
	}

	foreach ($registry['definition_keys'] as $definitionKey => $definitionMeta) {
		$expectedProfiles = $registry['profiles_by_type'][$definitionMeta['lhs_type']] ?? [];
		$actualProfiles = array_keys($definitionProfiles[$definitionKey] ?? []);
		sort($expectedProfiles);
		sort($actualProfiles);

		if ($expectedProfiles !== $actualProfiles) {
			$missingProfiles = array_values(array_diff($expectedProfiles, $actualProfiles));
			$extraProfiles = array_values(array_diff($actualProfiles, $expectedProfiles));
			$messageParts = [];
			if ($missingProfiles !== []) {
				$messageParts[] = 'missing profiles: ' . implode(', ', $missingProfiles);
			}
			if ($extraProfiles !== []) {
				$messageParts[] = 'unexpected profiles: ' . implode(', ', $extraProfiles);
			}
			$errors[] = om_validation_issue(
				'validation_error',
				'definition (' . $definitionKey . ')',
				'incomplete_profile_coverage',
				'The generated row set must cover the exact known profiles for the lhs_type; ' . implode('; ', $messageParts)
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
