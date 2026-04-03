<?php
declare(strict_types=1);

// POS-ARR-024
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 24;
}

$x = [];
$x["id"] = 24;
$x["name"] = "row-24";
$x["inner"] = [];
$x["inner"]["count"] = 48;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 24;
$x["inner"]["items"][] = 25;

$copy = $x;
$copy["name"] = "copy-24";
$copy["inner"]["count"] = 72;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
