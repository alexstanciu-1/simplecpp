<?php

// Coverage:
// - CLASS-STATIC-NS-001
// - CLASS-STATIC-NS-002
// - CLASS-STATIC-NS-003
// - CLASS-NS-003
// - NS-QUALIFIED-001

namespace StageTwo\Lib;

class Maker {
	public static function make(): int {
		return 21;
	}
}

function build_local(): int {
	$x = Maker::make();
	return $x;
}

namespace StageTwo\UseCase;

class Box {
}

function build_rooted(): int {
	$local = \StageTwo\Lib\Maker::make();
	return $local;
}

function build_objects(): int {
	$box = new Box();
	$maker = new \StageTwo\Lib\Maker();
	$value = $maker::make();
	return $value;
}

$a = build_rooted();
$b = build_objects();
$c = $a + $b;
