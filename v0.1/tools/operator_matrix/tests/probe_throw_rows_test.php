<?php

declare(strict_types=1);

// Exercise the real loader, seed builder and emitter for the fixed H09 row set.
foreach (['filesystem', 'cli', 'data_loader', 'row_builder', 'validator',
	'test_seed_builder', 'test_seed_validator', 'test_emitter'] as $owner) {
	require_once dirname(__DIR__) . '/src/' . $owner . '.php';
}
function probe_rows_check(bool $ok, string $message): void
{
	if (!$ok) { throw new RuntimeException($message); }
}
$output = $argv[1] ?? throw new RuntimeException('Expected fresh output directory');
probe_rows_check(!file_exists($output), 'Refusing to overwrite an existing output directory');
$expected = json_decode(file_get_contents(__DIR__ . '/fixtures/probe_throw_rows.json'), true, flags: JSON_THROW_ON_ERROR)['row_ids'];
probe_rows_check(count($expected) === 39 && count(array_unique($expected)) === 39, 'Expected 39 distinct row IDs');
$data = om_load_data(dirname(__DIR__, 3));
$registry = om_build_registry($data);
$familyRows = om_build_rows($data, $registry, 'language_probes_and_reset');
$validation = om_validate_rows($registry, $familyRows, 'language_probes_and_reset');
probe_rows_check($validation['ok'], 'Invalid family data: ' . json_encode($validation['errors']));
$rows = array_values(array_filter($familyRows,
	static fn(array $row): bool => in_array($row['row_id'], $expected, true)));
probe_rows_check(count($rows) === 39, 'Missing matrix rows');
$seeds = om_build_test_seeds($registry, $rows);
probe_rows_check(count($seeds) === 39, 'Missing or duplicated seeds');
probe_rows_check(om_validate_test_seeds($rows, $seeds)['ok'], 'Invalid selected seeds');
$actual = [];
$counts = [];
foreach ($seeds as $seed) {
	probe_rows_check(count($seed['source_row_ids']) === 1, 'Seed merged multiple rows');
	$actual[] = $seed['source_row_ids'][0];
	$counts[$seed['item_id']] = ($counts[$seed['item_id']] ?? 0) + 1;
	probe_rows_check($seed['test_seed_class'] === 'runtime_throw'
		&& $seed['outcome_class'] === 'negative_runtime', 'Shared runtime contract changed');
}
sort($actual); sort($expected);
probe_rows_check($actual === $expected, 'Seed-to-row mapping changed');
probe_rows_check($counts['count_value'] === 12 && $counts['empty_keyed'] === 14
	&& $counts['empty_value'] === 13, 'Unexpected operation counts');
$report = om_emit_matrix_tests($output, $seeds, om_parse_arguments([]));
probe_rows_check($report['php-matrix']['test_count'] === 39
	&& $report['php-matrix']['enabled_test_count'] === 39, 'Expected 39 enabled emitted tests');
om_write_json_file($output . '/selected-seeds.json', $seeds);
om_write_json_file($output . '/summary.json', ['passed' => true, 'rows' => 39, 'counts' => $counts, 'emission' => $report]);
echo "Probe throw rows: 39 unique seeds and emitted tests passed\n";
