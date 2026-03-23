<?php

function f($a, $b = 10, $c = 20) {
	return $a + $b + $c;
}

echo f(1), "\n";
echo f(1, 2), "\n";
echo f(1, 2, 3), "\n";

echo strlen("abc"), "\n";
echo substr("abcdef", 1, 3), "\n";
