<?php
declare(strict_types=1);

// POS-ARR-032
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 32;
}

$x = [];
$x["id"] = 32;
$x["name"] = "row-32";
$x["inner"] = [];
$x["inner"]["count"] = 64;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 32;
$x["inner"]["items"][] = 33;

$copy = $x;
$copy["name"] = "copy-32";
$copy["inner"]["count"] = 96;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
