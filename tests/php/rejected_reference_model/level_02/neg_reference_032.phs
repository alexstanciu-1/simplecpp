<?php
declare(strict_types=1);

// NEG-REF-032
// Expected: reject. Conditional binding is outside the Simple C++ reference model.

$a = 32;
$b = 33;
if ($a < $b) {
	$x =& $a;
} else {
	$x =& $b;
}

var_dump($x);
