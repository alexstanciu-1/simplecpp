<?php
declare(strict_types=1);

function f(?float $a): float {
	return $a ?? 2.5;
}

echo f(null), "\n";
echo f(1.5), "\n";
