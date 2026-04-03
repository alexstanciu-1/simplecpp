<?php
declare(strict_types=1);

// NEG-REF-022
// Expected: reject. Rebinding through a chain is outside the model.

$a = 22;
$b = 23;
$c = 24;
$x =& $a;
$y =& $x;
$y =& $c;

var_dump($a);
var_dump($b);
var_dump($c);
