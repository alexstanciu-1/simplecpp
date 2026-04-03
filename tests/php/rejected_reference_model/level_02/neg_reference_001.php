<?php
declare(strict_types=1);

// NEG-REF-001
// Expected: reject. Alias rebinding is outside the Simple C++ reference model.

$a = 1;
$b = 2;
$x =& $a;
$x =& $b;

var_dump($x);
