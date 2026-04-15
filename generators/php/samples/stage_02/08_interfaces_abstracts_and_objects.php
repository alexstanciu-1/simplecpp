<?php

// Coverage:
// - CLASS-INH-004
// - CLASS-ABS-006
// - CLASS-ABS-008
// - TYPE-PARAM-004
// - CLASS-NEW-001

interface Reader {
	function read(int $value): int;
}

abstract class BaseReader implements Reader {
	function keep(int $value): int {
		return $value;
	}

	abstract function read(int $value): int;
}

class LocalReader extends BaseReader {
	function read(int $value): int {
		$copy = $value;
		$copy = $copy + 1;
		return $copy;
	}
}

function pass_reader(Reader $reader, ?LocalReader $local): Reader {
	$copy = $reader;
	return $copy;
}

function use_reader(Reader $reader): int {
	return $reader->read(5);
}

$a = new LocalReader();
$b = pass_reader($a, $a);
$c = use_reader($b);

echo $c, "\n";
