<?php
declare(strict_types=1);

// POS-ARR-014
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 14;
}

$x = [];
$x["id"] = 14;
$x["name"] = "row-14";
$x["inner"] = [];
$x["inner"]["count"] = 28;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 14;
$x["inner"]["items"][] = 15;

$copy = $x;
$copy["name"] = "copy-14";
$copy["inner"]["count"] = 42;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
