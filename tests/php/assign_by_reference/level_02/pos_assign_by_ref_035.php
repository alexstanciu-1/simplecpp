<?php
declare(strict_types=1);

// POS-ASSIGNREF-035

function &get_bucket(array &$root): array
{
	return $root["bucket"];
}

$x = [];
$x["bucket"] = [];
$x["bucket"]["id"] = 35;

$bucket =& get_bucket($x);
$alias =& $bucket;
$alias["state"] = "s-35";

var_dump($x);
var_dump($bucket);
