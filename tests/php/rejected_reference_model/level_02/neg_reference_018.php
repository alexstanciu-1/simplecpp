<?php
declare(strict_types=1);

// NEG-REF-018
// Expected: reject. Returning one of multiple potential bindings by reference is outside the model.

function &pick_one(array &$root, bool $flag): int
{
	if ($flag) {
		return $root["a"];
	}

	return $root["b"];
}

$x = [];
$x["a"] = 18;
$x["b"] = 28;
$y =& pick_one($x, true);

var_dump($y);
