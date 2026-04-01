<?php
declare(strict_types=1);

function f(float $a): float {
	return $a + 1.5;
}

echo f(1.5), "\n";
