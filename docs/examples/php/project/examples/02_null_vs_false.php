<?php

declare(strict_types=1);

function example_lookup_state(string $mode): mixed
{
	if ($mode === "failure") {
		return false;
	}

	if ($mode === "missing") {
		return null;
	}

	return "ready";
}

function example_02_null_vs_false(): void
{
	$failed = example_lookup_state("failure");
	$missing = example_lookup_state("missing");
	$ready = example_lookup_state("ready");

	if ($failed === false) {
		echo "failure=false\n";
	}

	if ($missing === null) {
		echo "missing=null\n";
	}

	if ($ready === "ready") {
		echo "ready=ready\n";
	}
}
