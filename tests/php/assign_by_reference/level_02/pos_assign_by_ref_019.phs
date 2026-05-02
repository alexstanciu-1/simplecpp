<?php
declare(strict_types=1);

// POS-ASSIGNREF-019

function &get_bucket(array &$root): array
{
	return $root["bucket"];
}

$x = [];
$x["bucket"] = [];
$x["bucket"]["id"] = 19;

$bucket =& get_bucket($x);
$alias =& $bucket;
$alias["state"] = "s-19";

var_dump($x);
var_dump($bucket);
