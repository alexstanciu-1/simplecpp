<?php
declare(strict_types=1);

class Box {
	public function bump(int &$value): void {
		$value = $value + 5;
	}
}

$x = 10;
$box = new Box();
$box->bump($x);
echo $x, "
";
