<?php
declare(strict_types=1);

// Appending row arrays builds a list of records.
$x = [];
$x[] = ["id" => 1, "name" => "A"];
$x[] = ["id" => 2, "name" => "B"];

var_dump($x);
