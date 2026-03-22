<?php

// Coverage:
// - FUNC-REF-001
// - FUNC-REF-002
// - CLASS-MEMBER-018A
// - CLASS-MEMBER-018B

function bump(int &$a): void {
	$a = $a + 1;
}

function &pick_ref(): int {
	static $x = 1;
	return $x;
}

class Box {
	function set(int &$a): void {
		$a = $a + 1;
	}

	function &get_ref(): int {
		static $y = 2;
		return $y;
	}
}
