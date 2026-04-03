<?php
declare(strict_types=1);

// NEG-REF-039
// Expected: reject. Untyped reference returns are not allowed.

function &pick(array &$root)
{
	return $root["leaf"];
}

$x = [];
$x["leaf"] = 39;
$y =& pick($x);

var_dump($y);
