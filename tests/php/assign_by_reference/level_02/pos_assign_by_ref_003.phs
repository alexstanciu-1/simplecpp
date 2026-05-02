<?php
declare(strict_types=1);

// POS-ASSIGNREF-003

function &get_bucket(array &$root): array
{
	return $root["bucket"];
}

$x = [];
$x["bucket"] = [];
$x["bucket"]["id"] = 3;

$bucket =& get_bucket($x);
$alias =& $bucket;
$alias["state"] = "s-3";

var_dump($x);
var_dump($bucket);
