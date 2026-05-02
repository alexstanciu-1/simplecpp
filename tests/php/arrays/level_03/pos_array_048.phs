<?php
declare(strict_types=1);

// POS-ARR-048
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 48;
}

$x = [];
$x["id"] = 48;
$x["name"] = "row-48";
$x["inner"] = [];
$x["inner"]["count"] = 96;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 48;
$x["inner"]["items"][] = 49;

$copy = $x;
$copy["name"] = "copy-48";
$copy["inner"]["count"] = 144;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
