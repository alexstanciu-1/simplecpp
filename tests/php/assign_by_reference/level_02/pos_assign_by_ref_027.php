<?php
declare(strict_types=1);

// POS-ASSIGNREF-027

function &get_bucket(array &$root): array
{
	return $root["bucket"];
}

$x = [];
$x["bucket"] = [];
$x["bucket"]["id"] = 27;

$bucket =& get_bucket($x);
$alias =& $bucket;
$alias["state"] = "s-27";

var_dump($x);
var_dump($bucket);
