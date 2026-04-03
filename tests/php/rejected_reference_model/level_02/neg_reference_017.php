<?php
declare(strict_types=1);

// NEG-REF-017
// Expected: reject. Property/slot rebinding is outside the model.

$x = [];
$x["left"] = 17;
$x["right"] = 18;
$slot =& $x["left"];
$slot =& $x["right"];

var_dump($x);
