<?php

// Coverage:
// - CLASS-STATIC-NS-001
// - CLASS-STATIC-NS-003
// - CLASS-NS-002
// - CLASS-NS-003

namespace StageTwo\StaticMatrix;

class LocalFactory {
	public static function make(): int {
		return 41;
	}
}

class OtherFactory {
	public static function make(): int {
		return 1;
	}
}

function local_instance_call(): int {
	$local = new LocalFactory();
	$value = $local::make();
	return $value;
}

function rooted_instance_call(): int {
	$other = new \StageTwo\StaticMatrix\OtherFactory();
	$value = $other::make();
	return $value;
}

$a = local_instance_call();
$b = rooted_instance_call();
$c = $a + $b;

echo $c, "\n";
