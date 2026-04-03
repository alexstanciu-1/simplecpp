<?php
declare(strict_types=1);

// POS-ARR-036
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 36;
}

$x = [];
$x["id"] = 36;
$x["name"] = "row-36";
$x["inner"] = [];
$x["inner"]["count"] = 72;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 36;
$x["inner"]["items"][] = 37;

$copy = $x;
$copy["name"] = "copy-36";
$copy["inner"]["count"] = 108;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
