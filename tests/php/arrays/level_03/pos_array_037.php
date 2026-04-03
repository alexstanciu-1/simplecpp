<?php
declare(strict_types=1);

// POS-ARR-037
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 37;
}

$x = [];
$x["id"] = 37;
$x["name"] = "row-37";
$x["inner"] = [];
$x["inner"]["count"] = 74;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 37;
$x["inner"]["items"][] = 38;

$copy = $x;
$copy["name"] = "copy-37";
$copy["inner"]["count"] = 111;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
