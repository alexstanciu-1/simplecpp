<?php

declare(strict_types=1);

require_once "examples/01_typed_boundaries.php";
require_once "examples/02_null_vs_false.php";
require_once "examples/03_strict_comparisons.php";
require_once "examples/04_arrays_and_tables.php";
require_once "examples/05_json_decode_basic.php";
require_once "examples/06_filesystem_read_basic.php";
require_once "lib/Cli.php";

function example_ids(): array
{
	return [
		"01_typed_boundaries",
		"02_null_vs_false",
		"03_strict_comparisons",
		"04_arrays_and_tables",
		"05_json_decode_basic",
		"06_filesystem_read_basic",
	];
}

function run_all_examples(): void
{
	$exampleIds = example_ids();
	foreach ($exampleIds as $exampleId) {
		print_example_header($exampleId);
		run_registered_example($exampleId);
	}
}

function run_registered_example(string $exampleId): void
{
	if ($exampleId === "01_typed_boundaries") {
		example_01_typed_boundaries();
		return;
	}

	if ($exampleId === "02_null_vs_false") {
		example_02_null_vs_false();
		return;
	}

	if ($exampleId === "03_strict_comparisons") {
		example_03_strict_comparisons();
		return;
	}

	if ($exampleId === "04_arrays_and_tables") {
		example_04_arrays_and_tables();
		return;
	}

	if ($exampleId === "05_json_decode_basic") {
		example_05_json_decode_basic();
		return;
	}

	if ($exampleId === "06_filesystem_read_basic") {
		example_06_filesystem_read_basic();
		return;
	}
}
