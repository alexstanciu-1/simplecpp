<?php
declare(strict_types=1);

// POS-ASSIGNREF-010

$x = [];
$x["row"] = [];
$x["row"]["count"] = 10;

$row =& $x["row"];
$count =& $row["count"];
$count += 11;
$row["mark"] = "m-10";

var_dump($x);
var_dump($row);
