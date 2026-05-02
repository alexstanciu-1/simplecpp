<?php
declare(strict_types=1);

// POS-ARR-052
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 52;
}

$x = [];
$x["id"] = 52;
$x["name"] = "row-52";
$x["inner"] = [];
$x["inner"]["count"] = 104;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 52;
$x["inner"]["items"][] = 53;

$copy = $x;
$copy["name"] = "copy-52";
$copy["inner"]["count"] = 156;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
