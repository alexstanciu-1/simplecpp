<?php
declare(strict_types=1);

// POS-RETREF-010

function &pick_leaf(array &$root): int
{
	return $root["leaf"];
}

$x = [];
$x["leaf"] = 10;

$leaf =& pick_leaf($x);
$leaf += 20;

var_dump($x);
var_dump($leaf);
