<?php

// Coverage:
// - LIT-CONST-001
// - CAST-STRING-001
// - FUNC-DECL-004
// - TYPE-PARAM-001
// - VAR-CHAIN-002

function describe(int $count, float $ratio, bool $flag, string $label): string {
	$text = (string)$count;
	$copy = $label;
	return $copy;
}

function read_limit(): int {
	$limit = PHP_INT_MAX;
	return $limit;
}

function combine(int $left, int $right, bool $flag): int {
	$total = $left + $right;
	$copy = $total;
	return $copy;
}

$a = read_limit();
$b = 2.5;
$c = true;
$d = "limit";
$e = describe($a, $b, $c, $d);
$f = $e;
$g = combine(1, 2, $c);
$h = combine($g, 3, $c);

echo $f, "|", $h, "\n";
