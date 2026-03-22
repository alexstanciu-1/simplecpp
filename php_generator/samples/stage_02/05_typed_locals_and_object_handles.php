<?php

// Coverage:
// - TYPE-VAR-001
// - TYPE-VAR-002
// - TYPE-VAR-003
// - TYPE-VAR-004
// - CLASS-NEW-001
// - FUNC-DECL-002

class Item {
}

function typed_state(): string {
	$name /** string */ = "demo";
	$maybeName /** ?string */ = null;
	$item /** Item */ = new Item();
	$maybeItem /** ?Item */ = null;
	$copy = $name;
	$copy = "done";
	return $copy;
}

function typed_object_roundtrip(Item $item): Item {
	$copy /** Item */ = $item;
	return $copy;
}

$a = typed_state();
$b = new Item();
$c = typed_object_roundtrip($b);
