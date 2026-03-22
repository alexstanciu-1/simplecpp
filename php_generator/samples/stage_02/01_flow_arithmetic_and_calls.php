<?php

// Coverage:
// - LIT-INT-001
// - LIT-FLOAT-001
// - LIT-BOOL-001
// - LIT-STR-001
// - VAR-CHAIN-001
// - VAR-CHAIN-003
// - VAR-REASSIGN-001
// - FUNC-DECL-003
// - FUNC-DECL-004

function pair_sum(int $left, int $right): int {
	$total = $left + $right;
	return $total;
}

function compute_mix(int $seed, int $delta): int {
	$base = $seed;
	$next = $base + $delta;
	$copy = $next;
	$text = "stage2";
	$flag = true;
	$fraction = 3.5;
	$chainA = $chainB = 7;
	$chainB = $chainB + $seed;
	$sum = pair_sum($next, $chainB);
	$final = $sum + $copy;
	return $final;
}

$a = 10;
$b = 4;
$c = compute_mix($a, $b);
$d = pair_sum($c, $b);
$e = $d;
$e = $e + 1;
