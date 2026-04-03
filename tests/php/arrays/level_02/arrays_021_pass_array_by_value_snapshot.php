<?php
declare(strict_types=1);

function arrays_patch_value(array $row): void
{
    $row["name"] = "patched";
    $row["extra"] = 1;
}

$x = [];
$x[] = ["id" => 1, "name" => "Alex"];
arrays_patch_value($x[0]);

var_dump($x);
