<?php
declare(strict_types=1);

// NEG-REF-011
// Expected: reject. Property/slot rebinding is outside the model.

$x = [];
$x["left"] = 11;
$x["right"] = 12;
$slot =& $x["left"];
$slot =& $x["right"];

var_dump($x);
