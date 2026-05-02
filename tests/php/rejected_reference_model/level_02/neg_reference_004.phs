<?php
declare(strict_types=1);

// NEG-REF-004
// Expected: reject. Rebinding through a chain is outside the model.

$a = 4;
$b = 5;
$c = 6;
$x =& $a;
$y =& $x;
$y =& $c;

var_dump($a);
var_dump($b);
var_dump($c);
