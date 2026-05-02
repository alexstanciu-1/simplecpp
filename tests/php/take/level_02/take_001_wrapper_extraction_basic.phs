<?php
declare(strict_types=1);

$maybe /** nullable<int> */ = 7;
$out /** int */ = 0;
echo take($out, $maybe) ? "T\n" : "F\n";
echo $out, "\n";
$maybe = null;
echo take($out, $maybe) ? "T\n" : "F\n";
echo $out, "\n";

$orf /** result_or_false<int> */ = 21;
echo take($out, $orf) ? "T\n" : "F\n";
echo $out, "\n";
$orf = false;
echo take($out, $orf) ? "T\n" : "F\n";
echo $out, "\n";

$orb /** result_or_bool<int> */ = true;
$flag /** bool */ = false;
echo take($out, $flag, $orb) ? "T\n" : "F\n";
echo $flag ? "B1\n" : "B0\n";
echo $out, "\n";
$orb = false;
echo take($out, $flag, $orb) ? "T\n" : "F\n";
echo $flag ? "B1\n" : "B0\n";
echo $out, "\n";
$orb = 33;
echo take($out, $flag, $orb) ? "T\n" : "F\n";
echo $flag ? "B1\n" : "B0\n";
echo $out, "\n";
