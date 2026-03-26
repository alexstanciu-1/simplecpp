<?php
declare(strict_types=1);

function bump(int &$value): void {
	$value = $value + 1;
}

$x = 10;
bump($x);
echo $x, "
";
