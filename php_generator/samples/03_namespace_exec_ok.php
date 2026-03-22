<?php

// Coverage:
// - namespace executable flow consolidation
// - same-namespace function resolution

namespace A;

function helper($x) {
	return $x + 1;
}

$a = 1;
$b = helper($a);
$c = helper($b);
