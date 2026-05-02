<?php
declare(strict_types=1);

// NEG-REF-014
// Expected: reject. Conditional binding is outside the Simple C++ reference model.

$a = 14;
$b = 15;
if ($a < $b) {
	$x =& $a;
} else {
	$x =& $b;
}

var_dump($x);
