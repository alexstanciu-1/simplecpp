<?php
declare(strict_types=1);

// POS-RETREF-002

function &get_inner(array &$root): array
{
	return $root["inner"];
}

function &forward_inner(array &$root): array
{
	return get_inner($root);
}

$x = [];
$x["inner"] = [];
$x["inner"]["id"] = 2;

$inner =& forward_inner($x);
$inner["status"] = "ok-2";

var_dump($x);
var_dump($inner);
