<?php
declare(strict_types=1);

// Nullable ternary branch normalization.
function pick(bool $flag): ?int {
	if ($flag) {
		return 7;
	}

	return null;
}

$a = pick(true);
$b = pick(false);

$x = $a ? $b : 0;
$y = $b ? $a : 0;

echo ($x ?? 99), "\n";
echo ($y ?? 99), "\n";
