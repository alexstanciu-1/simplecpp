<?php

// Coverage:
// - CLASS-CONSTRUCT-001
// - CLASS-NEW-001
// - FUNC-DEFAULT-001
// - FUNC-DECL-003
// - VAR-CHAIN-003

class SeedBox {
	public int $seed;

	function __construct(int $seed) {
		$this->seed = $seed;
	}

	function read(): int {
		return $this->seed;
	}
}

function make_total(int $base = 3): int {
	$box = new SeedBox($base);
	$value = $box->read();
	$copy = $value;
	$extra = $copy + 2;
	return $extra;
}

$a = make_total();
$b = make_total(5);
$c = $a;
$d = $b;
