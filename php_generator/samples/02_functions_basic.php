<?php

// Coverage:
// - FUNC-DECL-* basic free function declaration/use
// - VAR-CHAIN-002
// - VAR-CHAIN-004
// - arithmetic in a simple supported shape

function add($a, $b) {
	$c = $a + $b;
	return $c;
}

function compute() {
	$x = 1;
	$y = 2;
	$z = add($x, $y);
	return $z;
}
