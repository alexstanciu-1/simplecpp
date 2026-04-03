<?php
declare(strict_types=1);

// POS-RETREF-028

function &pick_leaf(array &$root): int
{
	return $root["leaf"];
}

$x = [];
$x["leaf"] = 28;

$leaf =& pick_leaf($x);
$leaf += 56;

var_dump($x);
var_dump($leaf);
