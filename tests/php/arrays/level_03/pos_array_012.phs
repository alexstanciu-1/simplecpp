<?php
declare(strict_types=1);

// POS-ARR-012
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 12;
}

$x = [];
$x["id"] = 12;
$x["name"] = "row-12";
$x["inner"] = [];
$x["inner"]["count"] = 24;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 12;
$x["inner"]["items"][] = 13;

$copy = $x;
$copy["name"] = "copy-12";
$copy["inner"]["count"] = 36;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
