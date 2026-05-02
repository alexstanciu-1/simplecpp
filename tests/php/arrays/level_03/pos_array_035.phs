<?php
declare(strict_types=1);

// POS-ARR-035
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 35;
}

$x = [];
$x["id"] = 35;
$x["name"] = "row-35";
$x["inner"] = [];
$x["inner"]["count"] = 70;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 35;
$x["inner"]["items"][] = 36;

$copy = $x;
$copy["name"] = "copy-35";
$copy["inner"]["count"] = 105;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
