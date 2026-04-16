<?php
declare(strict_types=1);

// Return an explicit bool state.
function is_enabled(array $row): bool {
	return $row["enabled"] === true;
}

// Return an explicit nullable field.
function maybe_label(array $row): ?string {
	if ($row["label"] === null) {
		return null;
	}

	return $row["label"];
}

$row = [];
$row["enabled"] = true;
$row["label"] = null;

if (is_enabled($row) === true) {
	echo "enabled\n";
}

$label = maybe_label($row);
if ($label === null) {
	echo "missing\n";
}
