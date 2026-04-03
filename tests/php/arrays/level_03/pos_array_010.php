<?php
declare(strict_types=1);

// POS-ARR-010
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 10;
}

$x = [];
$x["id"] = 10;
$x["name"] = "row-10";
$x["inner"] = [];
$x["inner"]["count"] = 20;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 10;
$x["inner"]["items"][] = 11;

$copy = $x;
$copy["name"] = "copy-10";
$copy["inner"]["count"] = 30;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
