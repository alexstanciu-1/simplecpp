<?php
declare(strict_types=1);

// POS-RETREF-016

function &pick_leaf(array &$root): int
{
	return $root["leaf"];
}

$x = [];
$x["leaf"] = 16;

$leaf =& pick_leaf($x);
$leaf += 32;

var_dump($x);
var_dump($leaf);
