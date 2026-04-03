<?php
declare(strict_types=1);

// A small loop appending rows exercises repeated growth.
$x = [];
for ($i = 0; $i < 5; $i++) {
    $x[] = ["id" => $i, "name" => "N{$i}"];
}

var_dump($x);
