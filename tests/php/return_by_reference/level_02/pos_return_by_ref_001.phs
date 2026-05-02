<?php
declare(strict_types=1);

// POS-RETREF-001

function &pick_leaf(array &$root): int
{
	return $root["leaf"];
}

$x = [];
$x["leaf"] = 1;

$leaf =& pick_leaf($x);
$leaf += 2;

var_dump($x);
var_dump($leaf);
