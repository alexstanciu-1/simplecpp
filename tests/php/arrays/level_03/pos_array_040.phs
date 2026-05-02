<?php
declare(strict_types=1);

// POS-ARR-040
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 40;
}

$x = [];
$x["id"] = 40;
$x["name"] = "row-40";
$x["inner"] = [];
$x["inner"]["count"] = 80;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 40;
$x["inner"]["items"][] = 41;

$copy = $x;
$copy["name"] = "copy-40";
$copy["inner"]["count"] = 120;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
