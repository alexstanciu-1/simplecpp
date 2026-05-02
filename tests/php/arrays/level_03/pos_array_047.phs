<?php
declare(strict_types=1);

// POS-ARR-047
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 47;
}

$x = [];
$x["id"] = 47;
$x["name"] = "row-47";
$x["inner"] = [];
$x["inner"]["count"] = 94;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 47;
$x["inner"]["items"][] = 48;

$copy = $x;
$copy["name"] = "copy-47";
$copy["inner"]["count"] = 141;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
