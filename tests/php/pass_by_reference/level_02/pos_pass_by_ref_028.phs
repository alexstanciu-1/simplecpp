<?php
declare(strict_types=1);

// POS-BYREF-028

function append_note(array &$root, array &$row): void
{
	$row["note"] = "n-28";
	$root["count"] += 1;
}

$x = [];
$x["count"] = 0;
$x["row"] = [];
$x["row"]["id"] = 28;

append_note($x, $x["row"]);

var_dump($x);
