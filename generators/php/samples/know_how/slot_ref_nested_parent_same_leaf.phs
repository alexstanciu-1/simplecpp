<?php
declare(strict_types=1);

// Nested parent by-ref plus same leaf passed twice by-ref.
// Purpose: stress stable slot-backed references under parent growth and alias reuse.

function stress(int &$leaf1, array &$parent, int &$leaf2): void
{
	$parent["x"] = 100;
	$parent["y"] = 200;
	$leaf1 = 7;
	$leaf2 += 1;
}

$a = [
	"r" => [
		"s" => [
			"t" => 1,
		],
	],
];

stress($a["r"]["s"]["t"], $a["r"]["s"], $a["r"]["s"]["t"]);
var_dump($a);
