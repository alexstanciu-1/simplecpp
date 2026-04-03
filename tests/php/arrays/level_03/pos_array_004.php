<?php
declare(strict_types=1);

// POS-ARR-004
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 4;
}

$x = [];
$x["id"] = 4;
$x["name"] = "row-4";
$x["inner"] = [];
$x["inner"]["count"] = 8;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 4;
$x["inner"]["items"][] = 5;

$copy = $x;
$copy["name"] = "copy-4";
$copy["inner"]["count"] = 12;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
