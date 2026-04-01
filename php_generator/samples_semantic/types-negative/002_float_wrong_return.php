<?php
declare(strict_types=1);

function f(float $a): float {
	return "not-float";
}

echo f(1.0), "\n";
