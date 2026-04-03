<?php
declare(strict_types=1);

// POS-ASSIGNREF-034

$x = [];
$x["row"] = [];
$x["row"]["count"] = 34;

$row =& $x["row"];
$count =& $row["count"];
$count += 35;
$row["mark"] = "m-34";

var_dump($x);
var_dump($row);
