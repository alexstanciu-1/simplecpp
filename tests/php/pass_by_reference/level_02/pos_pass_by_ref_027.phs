<?php
declare(strict_types=1);

// POS-BYREF-027

function touch_row(array &$row, int &$leaf): void
{
	$row["seen"] = "yes-27";
	$leaf += 27;
	$row["leaf_after"] = $leaf;
}

$x = [];
$x["row"] = [];
$x["row"]["id"] = 27;
$x["row"]["leaf"] = 270;

touch_row($x["row"], $x["row"]["leaf"]);

var_dump($x);
