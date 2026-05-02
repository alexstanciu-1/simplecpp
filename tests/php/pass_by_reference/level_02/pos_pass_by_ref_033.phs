<?php
declare(strict_types=1);

// POS-BYREF-033

function touch_row(array &$row, int &$leaf): void
{
	$row["seen"] = "yes-33";
	$leaf += 33;
	$row["leaf_after"] = $leaf;
}

$x = [];
$x["row"] = [];
$x["row"]["id"] = 33;
$x["row"]["leaf"] = 330;

touch_row($x["row"], $x["row"]["leaf"]);

var_dump($x);
