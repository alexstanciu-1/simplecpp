<?php
declare(strict_types=1);

// POS-BYREF-007

function touch_row(array &$row, int &$leaf): void
{
	$row["seen"] = "yes-7";
	$leaf += 7;
	$row["leaf_after"] = $leaf;
}

$x = [];
$x["row"] = [];
$x["row"]["id"] = 7;
$x["row"]["leaf"] = 70;

touch_row($x["row"], $x["row"]["leaf"]);

var_dump($x);
