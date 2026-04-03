<?php
declare(strict_types=1);

// POS-ARR-013
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 13;
}

$x = [];
$x["id"] = 13;
$x["name"] = "row-13";
$x["inner"] = [];
$x["inner"]["count"] = 26;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 13;
$x["inner"]["items"][] = 14;

$copy = $x;
$copy["name"] = "copy-13";
$copy["inner"]["count"] = 39;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
