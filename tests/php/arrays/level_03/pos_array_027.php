<?php
declare(strict_types=1);

// POS-ARR-027
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 27;
}

$x = [];
$x["id"] = 27;
$x["name"] = "row-27";
$x["inner"] = [];
$x["inner"]["count"] = 54;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 27;
$x["inner"]["items"][] = 28;

$copy = $x;
$copy["name"] = "copy-27";
$copy["inner"]["count"] = 81;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
