<?php

class Box {
	public int $value;

	function __construct(int $value) {
		$this->value = $value;
	}
}

$box = new Box(5);
$alias = &$box;
$alias->value = $alias->value + 3;
echo $box->value, "|", $alias->value, "\n";
