<?php
declare(strict_types=1);

// NEG-REF-038
// Expected: reject. Conditional binding is outside the Simple C++ reference model.

$a = 38;
$b = 39;
if ($a < $b) {
	$x =& $a;
} else {
	$x =& $b;
}

var_dump($x);
