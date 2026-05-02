<?php
declare(strict_types=1);

// NEG-REF-013
// Expected: reject. Alias rebinding is outside the Simple C++ reference model.

$a = 13;
$b = 14;
$x =& $a;
$x =& $b;

var_dump($x);
