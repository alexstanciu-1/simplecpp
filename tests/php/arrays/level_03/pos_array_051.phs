<?php
declare(strict_types=1);

// POS-ARR-051
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 51;
}

$x = [];
$x["id"] = 51;
$x["name"] = "row-51";
$x["inner"] = [];
$x["inner"]["count"] = 102;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 51;
$x["inner"]["items"][] = 52;

$copy = $x;
$copy["name"] = "copy-51";
$copy["inner"]["count"] = 153;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
