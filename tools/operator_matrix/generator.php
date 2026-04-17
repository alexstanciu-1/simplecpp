#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/src/filesystem.php';
require_once __DIR__ . '/src/cli.php';
require_once __DIR__ . '/src/data_loader.php';
require_once __DIR__ . '/src/row_builder.php';
require_once __DIR__ . '/src/validator.php';
require_once __DIR__ . '/src/test_seed_builder.php';
require_once __DIR__ . '/src/test_seed_validator.php';

main($argv);

/**
 * @param list<string> $argv
 */
function main(array $argv): void
{
	$repoRoot = om_normalize_path(dirname(__DIR__, 2));
	$options = om_parse_arguments(array_slice($argv, 1));

	try {
		$data = om_load_data($repoRoot);
		$registry = om_build_registry($data);
		$rows = om_build_rows($data, $registry, $options['family']);
		$validation = om_validate_rows($registry, $rows, $options['family']);
		$testSeeds = om_build_test_seeds($registry, $rows);
		$testSeedValidation = om_validate_test_seeds($rows, $testSeeds);

		$outputRoot = $repoRoot . '/build/operator_matrix';
		om_ensure_directory($outputRoot);
		om_write_json_file($outputRoot . '/matrix.json', $rows);
		om_write_json_file($outputRoot . '/validation_report.json', $validation);
		om_write_json_file($outputRoot . '/test_seeds.json', $testSeeds);
		om_write_json_file($outputRoot . '/test_seed_validation_report.json', $testSeedValidation);

		if ($options['stdout']) {
			echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
		}

		echo 'Operator matrix generation completed.' . PHP_EOL;
		echo 'Rows: ' . count($rows) . PHP_EOL;
		echo 'Matrix: build/operator_matrix/matrix.json' . PHP_EOL;
		echo 'Validation report: build/operator_matrix/validation_report.json' . PHP_EOL;
		echo 'Test seeds: build/operator_matrix/test_seeds.json' . PHP_EOL;
		echo 'Test seed validation report: build/operator_matrix/test_seed_validation_report.json' . PHP_EOL;
		echo 'Validation errors: ' . $validation['error_count'] . PHP_EOL;
		echo 'Validation warnings: ' . $validation['warning_count'] . PHP_EOL;
		echo 'Test seeds: ' . count($testSeeds) . PHP_EOL;
		echo 'Test seed validation errors: ' . $testSeedValidation['error_count'] . PHP_EOL;
		echo 'Test seed validation warnings: ' . $testSeedValidation['warning_count'] . PHP_EOL;

		if (!$validation['ok'] || !$testSeedValidation['ok']) {
			exit(2);
		}
	} catch (Throwable $throwable) {
		fwrite(STDERR, 'Operator matrix generation failed: ' . $throwable->getMessage() . PHP_EOL);
		exit(1);
	}
}
