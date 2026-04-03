<?php
declare(strict_types=1);

// POS-ASSIGNREF-025

$a = 25;
$b =& $a;
$c =& $b;
$c += 25;

var_dump($a);
var_dump($b);
var_dump($c);
