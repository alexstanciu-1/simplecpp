<?php

declare(strict_types=1);

/**
 * Load the structured input data consumed by the operator-matrix generator.
 *
 * @return array<string, mixed>
 */
function om_load_data(string $repoRoot): array
{
	$dataRoot = om_normalize_path($repoRoot . '/specs/operator_matrix/data');
	$families = om_read_json_file($dataRoot . '/families.json');
	$types = om_read_json_file($dataRoot . '/types.json');
	$semantics = om_read_json_file($dataRoot . '/semantics.json');

	return [
		'families' => $families,
		'types' => $types,
		'semantics' => $semantics,
	];
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function om_build_registry(array $data): array
{
	$familiesById = [];
	$itemsByFamily = [];
	$profilesByType = [];
	$knownProfiles = [];
	$definitionKeys = [];

	foreach ($data['families']['families'] as $family) {
		$familyId = $family['family_id'];
		$familiesById[$familyId] = $family;
		$itemsByFamily[$familyId] = [];
		foreach ($family['items'] as $item) {
			$itemsByFamily[$familyId][$item['item_id']] = $item;
		}
	}

	foreach ($data['types']['types'] as $type) {
		$typeId = $type['type_id'];
		$profilesByType[$typeId] = $type['profiles'];
		foreach ($type['profiles'] as $profile) {
			$knownProfiles[$profile] = true;
		}
	}

	foreach ($data['semantics']['definitions'] as $definition) {
		$key = implode('|', [$definition['family_id'], $definition['item_id'], $definition['lhs_type']]);
		$definitionKeys[$key] = [
			'family_id' => $definition['family_id'],
			'item_id' => $definition['item_id'],
			'lhs_type' => $definition['lhs_type'],
		];
	}

	return [
		'families_by_id' => $familiesById,
		'items_by_family' => $itemsByFamily,
		'profiles_by_type' => $profilesByType,
		'known_profiles' => $knownProfiles,
		'definition_keys' => $definitionKeys,
	];
}
