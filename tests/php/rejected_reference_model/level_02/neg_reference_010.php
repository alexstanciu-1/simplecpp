<?php
declare(strict_types=1);

// NEG-REF-010
// Expected: reject. Rebinding through a chain is outside the model.

$a = 10;
$b = 11;
$c = 12;
$x =& $a;
$y =& $x;
$y =& $c;

var_dump($a);
var_dump($b);
var_dump($c);
