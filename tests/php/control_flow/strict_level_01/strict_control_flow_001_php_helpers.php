<?php
declare(strict_types=1);

$values = [10, 20];
$map = ["a" => 1, "b" => null];
$fallback = null;

echo count($values), "\n";
echo isset($map["a"]) ? "Y\n" : "N\n";
echo isset($map["b"]) ? "Y\n" : "N\n";
echo empty("") ? "E\n" : "N\n";
echo ($fallback ?? 7), "\n";
echo (false ? 1 : 2), "\n";
echo ("hi" === "hi") ? "I\n" : "N\n";
echo ("hi" !== "lo") ? "J\n" : "N\n";
