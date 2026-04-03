<?php
declare(strict_types=1);

// POS-BYREF-002

function append_note(array &$root, array &$row): void
{
	$row["note"] = "n-2";
	$root["count"] += 1;
}

$x = [];
$x["count"] = 0;
$x["row"] = [];
$x["row"]["id"] = 2;

append_note($x, $x["row"]);

var_dump($x);
