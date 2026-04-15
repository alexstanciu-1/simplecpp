<?php
declare(strict_types=1);

// Preserve false, null, and present scalar states with explicit strict checks.
$failed = false;
$missing = null;
$present = 7;

if ($failed === false) {
	echo "false\n";
}

if ($missing === null) {
	echo "null\n";
}

if ($present === 7) {
	echo "value\n";
}
