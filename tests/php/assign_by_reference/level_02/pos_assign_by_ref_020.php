<?php
declare(strict_types=1);

// POS-ASSIGNREF-020

$x = [];
$x["inner"] = [];
$x["inner"]["leaf"] = 20;

$inner =& $x["inner"];
$leaf =& $x["inner"]["leaf"];
$leaf += 5;
$inner["after"] = $leaf;

var_dump($x);
var_dump($inner);
var_dump($leaf);
