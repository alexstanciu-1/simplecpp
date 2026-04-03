<?php
declare(strict_types=1);

// POS-ASSIGNREF-013

$a = 13;
$b =& $a;
$c =& $b;
$c += 13;

var_dump($a);
var_dump($b);
var_dump($c);
