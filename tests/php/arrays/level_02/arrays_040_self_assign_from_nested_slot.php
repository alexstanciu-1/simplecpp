<?php
declare(strict_types=1);

$x = [];
$x[] = ["id" => 1, "name" => "Alex0"];
$x[0]["copy"] = $x[0]["name"];
$x[0]["name"] = "Bob";

var_dump($x);
