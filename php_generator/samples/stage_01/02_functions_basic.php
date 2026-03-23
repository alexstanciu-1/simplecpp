<?php

// Coverage:
// - FUNC-DECL-* basic free function declaration/use
// - VAR-CHAIN-002
// - VAR-CHAIN-004
// - arithmetic in a simple supported shape

function add(int $a, int $b): int {
	$c = $a + $b;
	return $c;
}

function compute(): int {
	$x = 1;
	$y = 2;
	$z = add($x, $y);
	return $z;
}

echo compute(), "\n";
