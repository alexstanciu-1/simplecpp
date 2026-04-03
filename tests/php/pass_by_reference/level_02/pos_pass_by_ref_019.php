<?php
declare(strict_types=1);

// POS-BYREF-019

function touch_row(array &$row, int &$leaf): void
{
	$row["seen"] = "yes-19";
	$leaf += 19;
	$row["leaf_after"] = $leaf;
}

$x = [];
$x["row"] = [];
$x["row"]["id"] = 19;
$x["row"]["leaf"] = 190;

touch_row($x["row"], $x["row"]["leaf"]);

var_dump($x);
