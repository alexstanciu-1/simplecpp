<?php
declare(strict_types=1);

function f(?int $a): int {
	return $a ?? 10;
}

echo f(null), "\n";
echo f(5), "\n";
