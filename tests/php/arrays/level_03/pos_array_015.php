<?php
declare(strict_types=1);

// POS-ARR-015
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 15;
}

$x = [];
$x["id"] = 15;
$x["name"] = "row-15";
$x["inner"] = [];
$x["inner"]["count"] = 30;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 15;
$x["inner"]["items"][] = 16;

$copy = $x;
$copy["name"] = "copy-15";
$copy["inner"]["count"] = 45;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
