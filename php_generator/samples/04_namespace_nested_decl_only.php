<?php

// Coverage:
// - NS-EXEC-005
// - CLASS-NS-001
// - parent namespace execution + nested declaration-only namespace

namespace A {
	$a = 1;

	namespace B {
		class X {
		}
	}

	$b = $a + 2;
}
