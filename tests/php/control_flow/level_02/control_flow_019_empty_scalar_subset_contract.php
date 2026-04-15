<?php
declare(strict_types=1);

// Reduced Prism++ empty() subset: null and empty string are empty; false, 0, and "0" are not.
$none = null;
$blank = "";
$falseValue = false;
$zero = 0;
$zeroString = "0";


echo empty($none) ? "T\n" : "F\n";
echo empty($blank) ? "T\n" : "F\n";
echo empty($falseValue) ? "T\n" : "F\n";
echo empty($zero) ? "T\n" : "F\n";
echo empty($zeroString) ? "T\n" : "F\n";
