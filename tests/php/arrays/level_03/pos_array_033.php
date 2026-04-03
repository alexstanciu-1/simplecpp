<?php
declare(strict_types=1);

// POS-ARR-033
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 33;
}

$x = [];
$x["id"] = 33;
$x["name"] = "row-33";
$x["inner"] = [];
$x["inner"]["count"] = 66;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 33;
$x["inner"]["items"][] = 34;

$copy = $x;
$copy["name"] = "copy-33";
$copy["inner"]["count"] = 99;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
