<?php

$a = 10;
$b = null;
$c = "x";

var_dump(isset($a));
var_dump(isset($b));
var_dump(isset($c));
var_dump(isset($missing));

var_dump(isset($a, $c));
var_dump(isset($a, $b));
var_dump(isset($a, $missing));
var_dump(isset($missing, $a));

$arr = ["k" => 123, "n" => null];
var_dump(isset($arr["k"]));
var_dump(isset($arr["n"]));
var_dump(isset($arr["missing"]));

$obj = new stdClass();
$obj->x = 7;
$obj->y = null;

var_dump(isset($obj->x));
var_dump(isset($obj->y));
var_dump(isset($obj->missing));

function make_obj(): stdClass {
	$o = new stdClass();
	$o->p = 99;
	return $o;
}

var_dump(isset(make_obj()->p));
var_dump(isset(make_obj()->missing));
