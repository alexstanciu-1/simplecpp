<?php
declare(strict_types=1);

// NEG-REF-026
// Expected: reject. Conditional binding is outside the Simple C++ reference model.

$a = 26;
$b = 27;
if ($a < $b) {
	$x =& $a;
} else {
	$x =& $b;
}

var_dump($x);
