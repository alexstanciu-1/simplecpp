<?php
declare(strict_types=1);

function &get_inner(array &$arr): array {
    return $arr["inner"];
}

function &bounce_ref(array &$arr): array {
    return get_inner($arr);
}

function local_alias(array &$arr) {
    $tmp =& bounce_ref($arr);
    $tmp["k"] = "changed-through-local-alias";
}

$x = [];
$x["inner"] = ["k" => "orig"];

local_alias($x);

var_dump($x);
