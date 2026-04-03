<?php
declare(strict_types=1);

// POS-BYREF-001

function touch_row(array &$row, int &$leaf): void
{
	$row["seen"] = "yes-1";
	$leaf += 1;
	$row["leaf_after"] = $leaf;
}

$x = [];
$x["row"] = [];
$x["row"]["id"] = 1;
$x["row"]["leaf"] = 10;

touch_row($x["row"], $x["row"]["leaf"]);

var_dump($x);
