<?php
declare(strict_types=1);

// POS-ARR-030
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 30;
}

$x = [];
$x["id"] = 30;
$x["name"] = "row-30";
$x["inner"] = [];
$x["inner"]["count"] = 60;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 30;
$x["inner"]["items"][] = 31;

$copy = $x;
$copy["name"] = "copy-30";
$copy["inner"]["count"] = 90;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
