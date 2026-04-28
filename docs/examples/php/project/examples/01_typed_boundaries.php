<?php

declare(strict_types=1);

function example_add(int $left, int $right): int
{
	return $left + $right;
}

function example_01_typed_boundaries(): void
{
	$row = [];
	$row["count"] = 41;
	$count /** int */ = $row["count"];
	$total = example_add($count, 1);

	echo "count=", $count, "\n";
	echo "total=", $total, "\n";
}
