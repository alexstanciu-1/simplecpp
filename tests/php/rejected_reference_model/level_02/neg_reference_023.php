<?php
declare(strict_types=1);

// NEG-REF-023
// Expected: reject. Property/slot rebinding is outside the model.

$x = [];
$x["left"] = 23;
$x["right"] = 24;
$slot =& $x["left"];
$slot =& $x["right"];

var_dump($x);
