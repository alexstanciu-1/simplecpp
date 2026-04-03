<?php
declare(strict_types=1);

function &get_inner(array &$arr): array {
    return $arr["inner"];
}

function &bounce_ref(array &$arr): array {
    return get_inner($arr);
}

$x = [];
$x["inner"] = ["k" => "orig"];

$y =& bounce_ref($x);
$y["k"] = "changed-via-bounce-ref";

var_dump($x);
var_dump($y);
