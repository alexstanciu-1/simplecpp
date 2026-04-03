<?php
declare(strict_types=1);

// NEG-REF-025
// Expected: reject. Alias rebinding is outside the Simple C++ reference model.

$a = 25;
$b = 26;
$x =& $a;
$x =& $b;

var_dump($x);
