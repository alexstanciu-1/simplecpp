<?php

// Coverage:
// - CLASS-NS-001
// - CLASS-NS-002
// - simple constructor-like object creation path via new

namespace Demo;

class User {
}

function make_user() {
	$u = new User();
	return $u;
}
