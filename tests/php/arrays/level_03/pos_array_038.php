<?php
declare(strict_types=1);

// POS-ARR-038
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 38;
}

$x = [];
$x["id"] = 38;
$x["name"] = "row-38";
$x["inner"] = [];
$x["inner"]["count"] = 76;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 38;
$x["inner"]["items"][] = 39;

$copy = $x;
$copy["name"] = "copy-38";
$copy["inner"]["count"] = 114;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
