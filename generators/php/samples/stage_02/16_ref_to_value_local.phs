<?php

class Box {
	public int $value;

	function __construct(int $value) {
		$this->value = $value;
	}
}

$box /** value Box */ = new Box(4);
$alias = &$box;
$alias->value = $alias->value + 2;
echo $box->value, "|", $alias->value, "\n";
