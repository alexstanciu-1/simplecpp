<?php

declare(strict_types=1);

function example_status_label(int $value): string
{
	if ($value === 0) {
		return "zero";
	}

	if ($value === 1) {
		return "one";
	}

	return "other";
}

function example_03_strict_comparisons(): void
{
	echo "0=", example_status_label(0), "\n";
	echo "1=", example_status_label(1), "\n";
	echo "2=", example_status_label(2), "\n";
}
