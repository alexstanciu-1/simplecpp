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
	$semantics = om_load_semantics_index($dataRoot);

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
		$key = om_build_definition_key(
			$definition['family_id'],
			$definition['item_id'],
			$definition['lhs_type'],
			$definition['rhs_type'] ?? null,
			$definition['third_type'] ?? null,
		);
		$definitionKeys[$key] = [
			'family_id' => $definition['family_id'],
			'item_id' => $definition['item_id'],
			'lhs_type' => $definition['lhs_type'],
			'rhs_type' => $definition['rhs_type'] ?? null,
			'third_type' => $definition['third_type'] ?? null,
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

function om_build_definition_key(string $familyId, string $itemId, string $lhsType, ?string $rhsType, ?string $thirdType): string
{
	return implode('|', [
		$familyId,
		$itemId,
		$lhsType,
		$rhsType ?? '-',
		$thirdType ?? '-',
	]);
}


/**
 * Resolve item arity, allowing an item-specific override over the family default.
 *
 * @param array<string, mixed> $family
 * @param array<string, mixed> $item
 */
function om_item_arity(array $family, array $item): int
{
	$itemArity = $item['arity'] ?? null;
	if (is_int($itemArity)) {
		return $itemArity;
	}
	if (is_numeric($itemArity)) {
		return (int) $itemArity;
	}
	return (int) ($family['arity'] ?? 1);
}

/**
 * @return array<string, mixed>
 */
function om_load_semantics_index(string $dataRoot): array
{
	$index = om_read_json_file($dataRoot . '/semantics.index.json');
	$definitions = $index['definitions'] ?? null;
	if (!is_array($definitions)) {
		throw new RuntimeException('Semantics index must contain a definitions array.');
	}

	foreach ($definitions as $definitionIndex => $definition) {
		if (!is_array($definition)) {
			throw new RuntimeException('Semantics definition entry must be an object.');
		}

		$rowFile = $definition['row_file'] ?? null;
		if (!is_string($rowFile) || $rowFile === '') {
			throw new RuntimeException('Semantics definition is missing row_file.');
		}

		$rowsPath = om_normalize_path($dataRoot . '/' . $rowFile);
		$definitions[$definitionIndex]['rows'] = om_read_tsv_rows($rowsPath, $definition);
	}

	$index['definitions'] = $definitions;
	return $index;
}

/**
 * @param array<string, mixed> $definition
 * @return list<array<string, mixed>>
 */
function om_read_tsv_rows(string $path, array $definition = []): array
{
	if (!is_file($path)) {
		throw new RuntimeException('TSV file not found: ' . $path);
	}

	$handle = fopen($path, 'rb');
	if ($handle === false) {
		throw new RuntimeException('Unable to open TSV file: ' . $path);
	}

	try {
		$header = fgetcsv($handle, 0, "\t");
		if (!is_array($header) || $header === []) {
			throw new RuntimeException('TSV file must begin with a header row: ' . $path);
		}

		$columns = array_map(
			static fn ($value): string => trim((string) $value),
			$header
		);
		om_assert_tsv_header_schema($columns, $path);

		$rows = [];
		$rowNumber = 1;
		while (($record = fgetcsv($handle, 0, "\t")) !== false) {
			$rowNumber++;
			if ($record === [null] || $record === []) {
				continue;
			}
			om_assert_tsv_record_width($record, $columns, $path, $rowNumber);

			$row = [];
			foreach ($columns as $columnIndex => $columnName) {
				$rawValue = array_key_exists($columnIndex, $record) ? $record[$columnIndex] : '';
				$row[$columnName] = om_normalize_tsv_scalar($rawValue);
			}
			om_assert_tsv_row_schema($row, $definition, $path, $rowNumber);

			$rows[] = $row;
		}

		return $rows;
	} finally {
		fclose($handle);
	}
}

/**
 * @return list<string>
 */
function om_tsv_schema_columns(): array
{
	return [
		'lhs_profile',
		'rhs_profile',
		'third_profile',
		'lhs_target_kind',
		'rhs_target_kind',
		'third_target_kind',
		'status',
		'behavior_class',
		'result_type',
		'result_profile',
		'edge_case',
		'edge_case_id',
		'test_seed_class',
		'diagnostic_class',
		'notes',
	];
}

/**
 * @param list<string> $columns
 */
function om_assert_tsv_header_schema(array $columns, string $path): void
{
	$expected = om_tsv_schema_columns();
	if ($columns !== $expected) {
		throw new RuntimeException(
			'Invalid TSV header for ' . $path . '. Expected: ' . implode("\t", $expected)
		);
	}

	$seen = [];
	foreach ($columns as $columnName) {
		if ($columnName === '') {
			throw new RuntimeException('TSV header contains an empty column name: ' . $path);
		}
		if (isset($seen[$columnName])) {
			throw new RuntimeException('TSV header contains duplicate column: ' . $columnName . ' in ' . $path);
		}
		$seen[$columnName] = true;
	}
}

/**
 * @param list<mixed> $record
 * @param list<string> $columns
 */
function om_assert_tsv_record_width(array $record, array $columns, string $path, int $rowNumber): void
{
	if (count($record) > count($columns)) {
		throw new RuntimeException(
			'TSV row has more fields than the header allows at ' . $path . ':' . $rowNumber
		);
	}
}

/**
 * @param array<string, mixed> $row
 * @param array<string, mixed> $definition
 */
function om_assert_tsv_row_schema(array $row, array $definition, string $path, int $rowNumber): void
{
	$arity = om_definition_arity($definition);
	$context = $path . ':' . $rowNumber;

	om_assert_tsv_required_string($row, 'lhs_profile', $context);
	om_assert_tsv_nullable_string($row, 'lhs_target_kind', $context);
	om_assert_tsv_required_string($row, 'status', $context);
	om_assert_tsv_nullable_string($row, 'behavior_class', $context);
	om_assert_tsv_nullable_string($row, 'result_type', $context);
	om_assert_tsv_nullable_string($row, 'result_profile', $context);
	om_assert_tsv_nullable_bool($row, 'edge_case', $context);
	om_assert_tsv_nullable_string($row, 'edge_case_id', $context);
	om_assert_tsv_nullable_string($row, 'test_seed_class', $context);
	om_assert_tsv_nullable_string($row, 'diagnostic_class', $context);
	om_assert_tsv_nullable_string($row, 'notes', $context);

	if ($arity >= 2) {
		om_assert_tsv_required_string($row, 'rhs_profile', $context);
	} else {
		om_assert_tsv_null($row, 'rhs_profile', $context, 'Unary definitions must not set rhs_profile.');
	}

	if ($arity >= 3) {
		om_assert_tsv_required_string($row, 'third_profile', $context);
	} else {
		om_assert_tsv_null($row, 'third_profile', $context, 'Non-ternary definitions must not set third_profile.');
	}

	if ($arity >= 2) {
		om_assert_tsv_nullable_string($row, 'rhs_target_kind', $context);
	} else {
		om_assert_tsv_null($row, 'rhs_target_kind', $context, 'Unary definitions must not set rhs_target_kind.');
	}

	if ($arity >= 3) {
		om_assert_tsv_nullable_string($row, 'third_target_kind', $context);
	} else {
		om_assert_tsv_null($row, 'third_target_kind', $context, 'Non-ternary definitions must not set third_target_kind.');
	}
}

/**
 * @param array<string, mixed> $definition
 */
function om_definition_arity(array $definition): int
{
	if (is_string($definition['third_type'] ?? null) && $definition['third_type'] !== '') {
		return 3;
	}
	if (is_string($definition['rhs_type'] ?? null) && $definition['rhs_type'] !== '') {
		return 2;
	}
	return 1;
}

/**
 * @param array<string, mixed> $row
 */
function om_assert_tsv_required_string(array $row, string $field, string $context): void
{
	$value = $row[$field] ?? null;
	if (!is_string($value) || $value === '') {
		throw new RuntimeException('TSV field must be a non-empty string for ' . $field . ' at ' . $context);
	}
}

/**
 * @param array<string, mixed> $row
 */
function om_assert_tsv_nullable_string(array $row, string $field, string $context): void
{
	$value = $row[$field] ?? null;
	if ($value !== null && !is_string($value)) {
		throw new RuntimeException('TSV field must be a string or empty for ' . $field . ' at ' . $context);
	}
}

/**
 * @param array<string, mixed> $row
 */
function om_assert_tsv_nullable_bool(array $row, string $field, string $context): void
{
	$value = $row[$field] ?? null;
	if ($value !== null && !is_bool($value)) {
		throw new RuntimeException('TSV field must be true, false, or empty for ' . $field . ' at ' . $context);
	}
}

/**
 * @param array<string, mixed> $row
 */
function om_assert_tsv_null(array $row, string $field, string $context, string $message): void
{
	if (($row[$field] ?? null) !== null) {
		throw new RuntimeException($message . ' At ' . $context);
	}
}

/**
 * @return bool|int|string|null
 */
function om_normalize_tsv_scalar(mixed $value): bool|int|string|null
{
	$text = trim((string) ($value ?? ''));
	if ($text === '') {
		return null;
	}
	if ($text === 'true') {
		return true;
	}
	if ($text === 'false') {
		return false;
	}
	if ($text === 'null') {
		return null;
	}
	if (preg_match('/^-?\d+$/', $text) === 1) {
		return (int) $text;
	}
	return $text;
}
