<?php

// Coverage:
// - CLASS-STATIC-NS-001
// - CLASS-STATIC-NS-002
// - CLASS-STATIC-NS-003

namespace A;

class X {
	public static function make() {
		return 1;
	}
}

function same_ns_static() {
	return X::make();
}

function rooted_static() {
	return \A\X::make();
}

function instance_static() {
	$x = new X();
	return $x::make();
}
