<?php
declare(strict_types=1);

// POS-BYREF-003

function touch_row(array &$row, int &$leaf): void
{
	$row["seen"] = "yes-3";
	$leaf += 3;
	$row["leaf_after"] = $leaf;
}

$x = [];
$x["row"] = [];
$x["row"]["id"] = 3;
$x["row"]["leaf"] = 30;

touch_row($x["row"], $x["row"]["leaf"]);

var_dump($x);
