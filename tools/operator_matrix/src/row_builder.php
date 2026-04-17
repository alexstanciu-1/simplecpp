<?php

declare(strict_types=1);

/**
 * Build deterministic matrix rows from the structured semantics definitions.
 *
 * @param array<string, mixed> $data
 * @param array<string, mixed> $registry
 * @return list<array<string, mixed>>
 */
function om_build_rows(array $data, array $registry, ?string $familyFilter): array
{
	$rows = [];

	foreach ($data['semantics']['definitions'] as $definition) {
		$familyId = $definition['family_id'];
		if ($familyFilter !== null && $familyFilter !== $familyId) {
			continue;
		}

		if (!isset($registry['families_by_id'][$familyId])) {
			continue;
		}

		$family = $registry['families_by_id'][$familyId];
		$itemId = $definition['item_id'];
		$item = $registry['items_by_family'][$familyId][$itemId] ?? ['source_item_refs' => []];

		foreach ($definition['rows'] as $rowDefinition) {
			$row = [
				'row_id' => om_build_row_id($familyId, $itemId, $definition['lhs_type'], $rowDefinition['lhs_profile']),
				'family_id' => $familyId,
				'item_id' => $itemId,
				'subfamily_id' => $family['subfamily_id'],
				'arity' => $family['arity'],
				'lhs_type' => $definition['lhs_type'],
				'rhs_type' => null,
				'third_type' => null,
				'lhs_profile' => $rowDefinition['lhs_profile'],
				'rhs_profile' => null,
				'third_profile' => null,
				'lhs_target_kind' => null,
				'rhs_target_kind' => null,
				'third_target_kind' => null,
				'status' => $rowDefinition['status'],
				'behavior_class' => $rowDefinition['behavior_class'] ?? null,
				'result_type' => $rowDefinition['result_type'] ?? null,
				'result_profile' => $rowDefinition['result_profile'] ?? null,
				'edge_case' => (bool) ($rowDefinition['edge_case'] ?? false),
				'edge_case_id' => $rowDefinition['edge_case_id'] ?? null,
				'test_seed_class' => $rowDefinition['test_seed_class'] ?? null,
				'diagnostic_class' => $rowDefinition['diagnostic_class'] ?? null,
				'source_family_refs' => $family['source_family_refs'],
				'source_item_refs' => $item['source_item_refs'] ?? [],
				'notes' => $rowDefinition['notes'] ?? null,
			];

			$rows[] = $row;
		}
	}

	usort($rows, static function (array $left, array $right): int {
		return [$left['family_id'], $left['item_id'], $left['lhs_type'], $left['lhs_profile']] <=> [$right['family_id'], $right['item_id'], $right['lhs_type'], $right['lhs_profile']];
	});

	return $rows;
}

function om_build_row_id(string $familyId, string $itemId, string $lhsType, string $lhsProfile): string
{
	return implode('|', [$familyId, $itemId, $lhsType, $lhsProfile]);
}
