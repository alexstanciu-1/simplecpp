<?php

// Coverage:
// - CLASS-NS-001
// - CLASS-NS-002
// - simple constructor-like object creation path via new

namespace Demo;

class User {
}

function make_user(): User {
	$u = new User();
	return $u;
}

$user = make_user();
echo "user-created", "\n";
