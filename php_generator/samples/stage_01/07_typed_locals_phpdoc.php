<?php

// Coverage:
// - TYPE-VAR-001
// - TYPE-VAR-002
// - TYPE-VAR-003
// - TYPE-VAR-004

class A {
}

function typed_locals(): string {
	$name /** string */ = "test";
	$maybeName /** ?string */ = null;
	$obj /** A */ = new A();
	$maybeObj /** ?A */ = null;

	return $name;
}

echo typed_locals(), "\n";
