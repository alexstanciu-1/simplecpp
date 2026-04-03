<?php
declare(strict_types=1);

// POS-ARR-009
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 9;
}

$x = [];
$x["id"] = 9;
$x["name"] = "row-9";
$x["inner"] = [];
$x["inner"]["count"] = 18;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 9;
$x["inner"]["items"][] = 10;

$copy = $x;
$copy["name"] = "copy-9";
$copy["inner"]["count"] = 27;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
