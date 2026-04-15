<?php
declare(strict_types=1);

function &get_inner(array &$arr): array {
    return $arr["inner"];
}

function alias_inner_then_copy_whole(array &$arr): void {
    $inner =& get_inner($arr);
    $copy = $arr;

    $copy["inner"]["k"] = "changed-copy";

    var_dump($inner);
    var_dump($copy);
}

$x = [];
$x["inner"] = ["k" => "orig"];

alias_inner_then_copy_whole($x);

var_dump($x);
