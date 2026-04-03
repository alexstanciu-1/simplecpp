<?php
declare(strict_types=1);

function arrays_stress_nested_parent(int &$leaf1, array &$parent, int &$leaf2): void
{
    $parent["x"] = 100;
    $parent["y"] = 200;
    $leaf1 = 7;
    $leaf2 += 1;
}

$a = [
    "r" => [
        "s" => [
            "t" => 1
        ]
    ]
];

arrays_stress_nested_parent($a["r"]["s"]["t"], $a["r"]["s"], $a["r"]["s"]["t"]);
var_dump($a);
