<?php
declare(strict_types=1);

// POS-ARR-039
// Safe positive array case within the documented reduced PHP-array subset.

function bump_slot(int &$slot): void
{
	$slot += 39;
}

$x = [];
$x["id"] = 39;
$x["name"] = "row-39";
$x["inner"] = [];
$x["inner"]["count"] = 78;
$x["inner"]["items"] = [];
$x["inner"]["items"][] = 39;
$x["inner"]["items"][] = 40;

$copy = $x;
$copy["name"] = "copy-39";
$copy["inner"]["count"] = 117;

bump_slot($x["inner"]["count"]);

var_dump($x);
var_dump($copy);
