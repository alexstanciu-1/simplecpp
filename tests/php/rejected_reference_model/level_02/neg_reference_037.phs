<?php
declare(strict_types=1);

// NEG-REF-037
// Expected: reject. Alias rebinding is outside the Simple C++ reference model.

$a = 37;
$b = 38;
$x =& $a;
$x =& $b;

var_dump($x);
