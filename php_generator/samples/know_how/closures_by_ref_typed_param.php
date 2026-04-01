<?php

$inc = function (int &$x): void {
    $x = $x + 1;
};

$n = 3;
$inc($n);
echo $n;
