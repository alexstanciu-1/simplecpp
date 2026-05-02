<?php
declare(strict_types=1);

function &pick_ref(int &$value): int {
	return $value;
}

$x = 10;
$y =& pick_ref($x);
$y = 20;
echo $x, "
";
