<?php
declare(strict_types=1);

namespace scpp {

require_once __DIR__ . "/strings.php";
require_once __DIR__ . "/filesystem.php";
require_once __DIR__ . "/snapshot.php";
require_once __DIR__ . "/json_document.php";
require_once __DIR__ . "/collections.php";
require_once __DIR__ . "/file_locks.php";
require_once __DIR__ . "/processes.php";

function enum_name(\UnitEnum $value): string {
    return $value->name;
}

// PHP approximations; wrapper intent is explicit in the operation name.
function take_nullable(mixed &$out, mixed $value): bool {
	if ($value === null) {
		return false;
	}
	$out = $value;
	return true;
}

function take_false(mixed &$out, mixed $value): bool {
	if ($value === false) {
		return false;
	}
	$out = $value;
	return true;
}

function take_bool(mixed &$out, bool &$flag, mixed $value): bool {
	if (is_bool($value)) {
		$flag = $value;
		return $value;
	}
	$out = $value;
	return true;
}

// Native byte slicing: invalid ranges are empty; overlong lengths are clamped.
function string_byte_slice(string $value, int $offset, int $length): string {
	if ($offset < 0 || $length < 0 || $offset > strlen($value)) {
		return '';
	}
	return substr($value, $offset, $length);
}

// Unsigned byte value, or -1 for an offset outside the string.
function string_byte_at(string $value, int $offset): int {
    if ($offset < 0 || $offset >= strlen($value)) {
        return -1;
    }
    return ord($value[$offset]);
}

// Stabilize both native handles at the exception base before identity comparison.
function same_exception(\Throwable $left, \Throwable $right): bool {
    return $left === $right;
}

}

namespace { require_once __DIR__ . '/global_functions.php'; }
