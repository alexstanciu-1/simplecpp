<?php
declare(strict_types=1);

// POS-BYREF-037

function touch_row(array &$row, int &$leaf): void
{
	$row["seen"] = "yes-37";
	$leaf += 37;
	$row["leaf_after"] = $leaf;
}

$x = [];
$x["row"] = [];
$x["row"]["id"] = 37;
$x["row"]["leaf"] = 370;

touch_row($x["row"], $x["row"]["leaf"]);

var_dump($x);
