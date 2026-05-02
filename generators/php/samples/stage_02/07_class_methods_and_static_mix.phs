<?php

// Coverage:
// - CLASS-SPLIT-001
// - CLASS-METHOD-001
// - CLASS-METHOD-002
// - CLASS-THIS-001
// - CLASS-CONSTRUCT-001
// - CLASS-NEW-001

class Counter {
	public int $value;

	function __construct(int $value) {
		$this->value = $value;
	}

	function read(): int {
		return $this->value;
	}

	static function make_seed(int $base): int {
		$result = $base + 1;
		return $result;
	}
}

function use_counter(int $seed): int {
	$counter = new Counter($seed);
	$left = $counter->read();
	$right = Counter::make_seed($left);
	$total = $left + $right;
	return $total;
}

$a = use_counter(5);
$b = $a;

echo $b, "\n";
