<?php
declare(strict_types=1);

// NEG-REF-020
// Expected: reject. Conditional binding is outside the Simple C++ reference model.

$a = 20;
$b = 21;
if ($a < $b) {
	$x =& $a;
} else {
	$x =& $b;
}

var_dump($x);
