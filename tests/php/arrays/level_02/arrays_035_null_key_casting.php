<?php
declare(strict_types=1);

$x = [];
$x[null] = "empty-key";
$x[""] = "empty-string";

var_dump($x);
