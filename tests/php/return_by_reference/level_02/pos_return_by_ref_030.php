<?php
declare(strict_types=1);

// POS-RETREF-030

function &pick_name(array &$root): string
{
	return $root["name"];
}

$x = [];
$x["name"] = "name-30";

$name =& pick_name($x);
$name = $name . "-updated";

var_dump($x);
var_dump($name);
