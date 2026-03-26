<?php
declare(strict_types=1);

function &pick_ref(int &$value): int {
	return $value;
}

$x = 10;
$ref =& pick_ref($x);
$ref = 30;
echo $x, "
";
