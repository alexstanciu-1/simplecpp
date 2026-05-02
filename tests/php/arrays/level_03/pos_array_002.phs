<?php
declare(strict_types=1);

// POS-ARR-002
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 2;
}

$x = [];
$x["id"] = 2;
$x["name"] = "row-2";
$x["inner"] = [];
$x["inner"]["count"] = 4;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 2;
$x["inner"]["items"][] = 3;

$copy = $x;
$copy["name"] = "copy-2";
$copy["inner"]["count"] = 6;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
