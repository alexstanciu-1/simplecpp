<?php
declare(strict_types=1);

// Nullable return value.
function pick(bool $flag): ?int {
	if ($flag) {
		return 7;
	}

	return null;
}

$a = pick(true);
$b = pick(false);

echo ($a ?? 0), "
";
echo ($b ?? 0), "
";
