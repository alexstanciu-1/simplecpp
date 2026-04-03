<?php
declare(strict_types=1);

// POS-ARR-023
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 23;
}

$x = [];
$x["id"] = 23;
$x["name"] = "row-23";
$x["inner"] = [];
$x["inner"]["count"] = 46;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 23;
$x["inner"]["items"][] = 24;

$copy = $x;
$copy["name"] = "copy-23";
$copy["inner"]["count"] = 69;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
