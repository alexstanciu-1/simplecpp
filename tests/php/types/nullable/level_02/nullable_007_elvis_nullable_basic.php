<?php
declare(strict_types=1);

// Nullable elvis should evaluate the left side once.
function pick(bool $flag): ?int {
	if ($flag) {
		return 7;
	}

	return null;
}

$a = pick(true);
$b = pick(false);

echo ($a ?: 0), "\n";
echo ($b ?: 0), "\n";
