<?php

$arr = ["k" => ["inner" => 5], "n" => null];
$obj = new stdClass();
$obj->child = new stdClass();
$obj->child->value = 11;

var_dump(isset(($arr["k"])));
var_dump(isset((($arr["k"]["inner"]))));
var_dump(isset(($obj->child->value)));
var_dump(isset((($obj->child->missing))));

unset($arr["k"]["inner"]);
var_dump(isset($arr["k"]["inner"]));

unset($obj->child->value);
var_dump(isset($obj->child->value));
