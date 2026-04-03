<?php
declare(strict_types=1);

// POS-ARR-008
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 8;
}

$x = [];
$x["id"] = 8;
$x["name"] = "row-8";
$x["inner"] = [];
$x["inner"]["count"] = 16;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 8;
$x["inner"]["items"][] = 9;

$copy = $x;
$copy["name"] = "copy-8";
$copy["inner"]["count"] = 24;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
