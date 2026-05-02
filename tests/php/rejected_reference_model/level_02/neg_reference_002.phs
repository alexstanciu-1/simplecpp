<?php
declare(strict_types=1);

// NEG-REF-002
// Expected: reject. Conditional binding is outside the Simple C++ reference model.

$a = 2;
$b = 3;
if ($a < $b) {
	$x =& $a;
} else {
	$x =& $b;
}

var_dump($x);
