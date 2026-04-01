<?php
declare(strict_types=1);

function f(bool $a): bool {
	return !$a;
}

echo f(true) ? "1" : "0", "\n";
