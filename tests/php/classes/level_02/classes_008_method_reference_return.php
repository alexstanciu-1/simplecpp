<?php
declare(strict_types=1);

class Box {
	public int $value = 10;

	public function &pick(): int {
		return $this->value;
	}
}

$box = new Box();
$ref =& $box->pick();
$ref = 42;
echo $box->value, "
";
