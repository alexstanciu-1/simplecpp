<?php

$a = 10;
$b = 20;
$c = 30;

unset($a);
var_dump(isset($a), isset($b), isset($c));

unset($b, $c);
var_dump(isset($a), isset($b), isset($c));

$arr = ["x" => 1, "y" => 2, "z" => null];
unset($arr["x"]);
var_dump(isset($arr["x"]), isset($arr["y"]), isset($arr["z"]));

unset($arr["y"], $arr["z"]);
var_dump(isset($arr["x"]), isset($arr["y"]), isset($arr["z"]));

$obj = new stdClass();
$obj->a = 1;
$obj->b = 2;

unset($obj->a);
var_dump(isset($obj->a), isset($obj->b));

unset($obj->b);
var_dump(isset($obj->a), isset($obj->b));
