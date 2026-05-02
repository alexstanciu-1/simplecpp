<?php
declare(strict_types=1);

// NEG-REF-028
// Expected: reject. Rebinding through a chain is outside the model.

$a = 28;
$b = 29;
$c = 30;
$x =& $a;
$y =& $x;
$y =& $c;

var_dump($a);
var_dump($b);
var_dump($c);
