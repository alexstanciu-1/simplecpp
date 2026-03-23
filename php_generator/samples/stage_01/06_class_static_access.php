<?php

// Coverage:
// - CLASS-STATIC-NS-001
// - CLASS-STATIC-NS-002
// - CLASS-STATIC-NS-003

namespace A;

class X {
	public static function make(): int {
		return 1;
	}
}

function same_ns_static(): int {
	return X::make();
}

function rooted_static(): int {
	return \A\X::make();
}

function instance_static(): int {
	$x = new X();
	return $x::make();
}

echo same_ns_static(), "|", rooted_static(), "|", instance_static(), "\n";
