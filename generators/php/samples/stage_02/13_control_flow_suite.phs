<?php

$i = 0;
$sum = 0;

while ($i < 5) {
	$i++;
	if ($i == 2) {
		continue;
	}
	if ($i > 3) {
		break;
	}
	$sum = $sum + $i;
}

for ($j = 0; $j < 2; $j++) {
	$sum = $sum + $j;
}

$x = ($sum > 0) ? 1 : 2;
$y = $sum ?: 2;

switch ($x) {
	case 1:
		echo "ok\n";
		break;
	default:
		echo "bad\n";
}

do {
	$y = $y - 1;
} while ($y > 3);

echo $y, "\n";
