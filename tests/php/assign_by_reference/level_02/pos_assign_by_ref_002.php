<?php
declare(strict_types=1);

// POS-ASSIGNREF-002

$x = [];
$x["row"] = [];
$x["row"]["count"] = 2;

$row =& $x["row"];
$count =& $row["count"];
$count += 3;
$row["mark"] = "m-2";

var_dump($x);
var_dump($row);
