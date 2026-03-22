<?php

// Coverage:
// - FUNC-DEFAULT-001
// - FUNC-NULLABLE-001
// - FUNC-VOID-001
// - TYPE-PARAM-002
// - CAST-STRING-001
// - VAR-CHAIN-002

function normalize_name(string $name, int $suffix = 1): string {
	$copy = $name;
	$number = (string)$suffix;
	$result = $copy;
	return $result;
}

function maybe_id(?int $value): ?int {
	return $value;
}

function touch_name(string $name): void {
	$local = $name;
	$copy = $local;
	return;
}

$name = "alpha";
$next = normalize_name($name, 2);
$maybe = maybe_id(5);
$again = maybe_id($maybe);
$text = normalize_name($next);
$copy = $text;
$copy = normalize_name($copy, 3);
