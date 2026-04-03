<?php
declare(strict_types=1);

// Nested rows remain independent after array copy-by-value.
$a = [];
$a["row"] = ["id" => 1, "name" => "Alex"];
$b = $a;
$a["row"]["name"] = "Bob";

var_dump($a);
var_dump($b);
