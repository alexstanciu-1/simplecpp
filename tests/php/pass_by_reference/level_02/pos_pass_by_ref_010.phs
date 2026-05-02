<?php
declare(strict_types=1);

// POS-BYREF-010

function append_note(array &$root, array &$row): void
{
	$row["note"] = "n-10";
	$root["count"] += 1;
}

$x = [];
$x["count"] = 0;
$x["row"] = [];
$x["row"]["id"] = 10;

append_note($x, $x["row"]);

var_dump($x);
