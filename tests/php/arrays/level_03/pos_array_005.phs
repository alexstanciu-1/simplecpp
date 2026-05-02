<?php
declare(strict_types=1);

// POS-ARR-005
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 5;
}

$x = [];
$x["id"] = 5;
$x["name"] = "row-5";
$x["inner"] = [];
$x["inner"]["count"] = 10;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 5;
$x["inner"]["items"][] = 6;

$copy = $x;
$copy["name"] = "copy-5";
$copy["inner"]["count"] = 15;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
