<?php
declare(strict_types=1);

// POS-ASSIGNREF-033

$a = 33;
$b =& $a;
$c =& $b;
$c += 33;

var_dump($a);
var_dump($b);
var_dump($c);
