<?php
declare(strict_types=1);

// POS-ARR-020
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 20;
}

$x = [];
$x["id"] = 20;
$x["name"] = "row-20";
$x["inner"] = [];
$x["inner"]["count"] = 40;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 20;
$x["inner"]["items"][] = 21;

$copy = $x;
$copy["name"] = "copy-20";
$copy["inner"]["count"] = 60;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
