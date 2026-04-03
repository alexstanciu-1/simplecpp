<?php
declare(strict_types=1);

// NEG-REF-008
// Expected: reject. Conditional binding is outside the Simple C++ reference model.

$a = 8;
$b = 9;
if ($a < $b) {
	$x =& $a;
} else {
	$x =& $b;
}

var_dump($x);
