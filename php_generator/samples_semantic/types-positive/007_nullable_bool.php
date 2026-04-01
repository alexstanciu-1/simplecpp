<?php
declare(strict_types=1);

function f(?bool $a): bool {
	return $a ?? true;
}

echo f(null) ? "1" : "0", "\n";
echo f(false) ? "1" : "0", "\n";
