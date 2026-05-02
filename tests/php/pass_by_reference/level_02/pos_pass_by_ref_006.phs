<?php
declare(strict_types=1);

// POS-BYREF-006

function append_note(array &$root, array &$row): void
{
	$row["note"] = "n-6";
	$root["count"] += 1;
}

$x = [];
$x["count"] = 0;
$x["row"] = [];
$x["row"]["id"] = 6;

append_note($x, $x["row"]);

var_dump($x);
