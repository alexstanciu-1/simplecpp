<?php
declare(strict_types=1);

// NEG-REF-005
// Expected: reject. Property/slot rebinding is outside the model.

$x = [];
$x["left"] = 5;
$x["right"] = 6;
$slot =& $x["left"];
$slot =& $x["right"];

var_dump($x);
