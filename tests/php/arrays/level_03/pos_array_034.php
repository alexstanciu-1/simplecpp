<?php
declare(strict_types=1);

// POS-ARR-034
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 34;
}

$x = [];
$x["id"] = 34;
$x["name"] = "row-34";
$x["inner"] = [];
$x["inner"]["count"] = 68;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 34;
$x["inner"]["items"][] = 35;

$copy = $x;
$copy["name"] = "copy-34";
$copy["inner"]["count"] = 102;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
