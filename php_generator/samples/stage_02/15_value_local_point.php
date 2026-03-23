<?php

class Point {
	public int $x;
	public int $y;

	function __construct(int $x, int $y) {
		$this->x = $x;
		$this->y = $y;
	}

	function sum(): int {
		return $this->x + $this->y;
	}
}

$p /** value Point */ = new Point(2, 3);
echo $p->sum(), "\n";
