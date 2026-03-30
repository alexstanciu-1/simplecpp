<?php

class Counter {
	public $v = 0;

	public function inc(): int {
		$this->v = $this->v + 1;
		return $this->v;
	}
}

$a = 2;
$b = 3;
$c = 4;
$obj = new Counter();
$n /** ?int */ = null;

# not working!!!
