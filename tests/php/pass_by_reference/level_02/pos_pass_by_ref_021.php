<?php
declare(strict_types=1);

// POS-BYREF-021

function touch_row(array &$row, int &$leaf): void
{
	$row["seen"] = "yes-21";
	$leaf += 21;
	$row["leaf_after"] = $leaf;
}

$x = [];
$x["row"] = [];
$x["row"]["id"] = 21;
$x["row"]["leaf"] = 210;

touch_row($x["row"], $x["row"]["leaf"]);

var_dump($x);
