<?php

// Coverage:
// - NS-EXEC-006 (should be rejected)
// - LIT-NULL-001 untyped null assignment (should be rejected)
// - CLASS-NS-004 qualified self-reference construction in same namespace (should be rejected)

namespace A\B {
	$a = null;

	function make_x() {
		return new A\B\X();
	}

	compute(1);

	namespace C {
		compute(2);
	}

	compute(3);
}
