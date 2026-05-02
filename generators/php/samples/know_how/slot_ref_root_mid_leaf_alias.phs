<?php
declare(strict_types=1);

// Root by-ref, nested parent by-ref, and nested leaf by-ref all alias the same chain.
// Purpose: stress alias coherence across different entry points in one call.

function twist(array &$root, int &$leaf, array &$mid): void
{
	$mid["u"] = 50;
	$root["r"]["s"]["v"] = 60;
	$leaf += 5;
	$mid["t"] = $leaf;
}

$a = [
	"r" => [
		"s" => [
			"t" => 1,
		],
	],
];

twist($a, $a["r"]["s"]["t"], $a["r"]["s"]);
var_dump($a);
