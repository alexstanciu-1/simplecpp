<?php

// Coverage:
// - FUNC-REF-001
// - FUNC-REF-002
// - FUNC-DECL-003
// - VAR-REASSIGN-001

function bump(int &$value): void {
	$value = $value + 1;
	return;
}

function add_twice(int &$value): void {
	bump($value);
	bump($value);
	return;
}

function &choose_ref(int &$value): int {
	return $value;
}

function materialize(int $seed): int {
	$copy = $seed;
	add_twice($copy);
	$alias = &choose_ref($copy);
	$alias = $alias + 3;
	return $copy;
}

$a = 1;
$b = materialize($a);
$c = $b;

echo $c, "\n";
