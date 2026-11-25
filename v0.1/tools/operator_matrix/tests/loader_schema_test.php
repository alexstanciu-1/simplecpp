<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/filesystem.php';
require_once dirname(__DIR__) . '/src/data_loader.php';

$repoRoot = om_normalize_path(dirname(__DIR__, 3));
$tempRoot = $repoRoot . '/build/operator_matrix_loader_schema_test';

om_delete_tree_if_present($tempRoot);
om_ensure_directory($tempRoot);

try {
	om_run_loader_schema_test_suite($repoRoot, $tempRoot);
	echo "loader_schema_test: ok\n";
} finally {
	om_delete_tree_if_present($tempRoot);
}

function om_run_loader_schema_test_suite(string $repoRoot, string $tempRoot): void
{
	$data = om_load_data($repoRoot);
	if (($data['semantics']['definitions'] ?? []) === []) {
		throw new RuntimeException('Expected semantics definitions from the live dataset.');
	}

	$canonicalHeader = implode("\t", om_tsv_schema_columns()) . PHP_EOL;

	om_expect_runtime_exception(
		static function () use ($tempRoot): void {
			$path = $tempRoot . '/bad_header.tsv';
			file_put_contents($path, "lhs_profile\tstatus\nbool.false\tsupported\n");
			om_read_tsv_rows($path, ['family_id' => 'x', 'item_id' => 'y', 'lhs_type' => 'bool_t']);
		},
		'Invalid TSV header'
	);

	om_expect_runtime_exception(
		static function () use ($tempRoot, $canonicalHeader): void {
			$path = $tempRoot . '/unary_rhs_profile.tsv';
			$row = implode("\t", [
				'bool.false',
				'bool.true',
				'',
				'',
				'',
				'',
				'supported',
				'deterministic_value',
				'bool_t',
				'bool.false',
				'',
				'',
				'',
				'',
				'unary row with rhs_profile',
			]) . PHP_EOL;
			file_put_contents($path, $canonicalHeader . $row);
			om_read_tsv_rows($path, ['family_id' => 'x', 'item_id' => 'y', 'lhs_type' => 'bool_t']);
		},
		'Unary definitions must not set rhs_profile'
	);
}

function om_expect_runtime_exception(callable $callback, string $messageFragment): void
{
	try {
		$callback();
	} catch (RuntimeException $exception) {
		if (str_contains($exception->getMessage(), $messageFragment)) {
			return;
		}

		throw new RuntimeException(
			'Expected RuntimeException containing "' . $messageFragment . '", got: ' . $exception->getMessage(),
			0,
			$exception
		);
	}

	throw new RuntimeException('Expected RuntimeException containing "' . $messageFragment . '".');
}

function om_delete_tree_if_present(string $path): void
{
	if (!is_dir($path)) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($iterator as $entry) {
		if ($entry->isDir()) {
			rmdir($entry->getPathname());
			continue;
		}

		unlink($entry->getPathname());
	}

	rmdir($path);
}
