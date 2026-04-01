<?php
declare(strict_types=1);

function f(?string $a): string {
	return $a ?? "default";
}

echo f(null), "\n";
echo f("ok"), "\n";
