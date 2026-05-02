<?php
declare(strict_types=1);

// POS-RETREF-025

function &pick_leaf(array &$root): int
{
	return $root["leaf"];
}

$x = [];
$x["leaf"] = 25;

$leaf =& pick_leaf($x);
$leaf += 50;

var_dump($x);
var_dump($leaf);
