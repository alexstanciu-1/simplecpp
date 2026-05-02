<?php

// Coverage:
// - NS-DECL-002
// - NS-EXEC-005
// - NS-QUALIFIED-003
// - CLASS-NS-002
// - CLASS-STATIC-NS-001

namespace StageTwo\Exec {
	class Worker {
		public static function make(): int {
			return 11;
		}
	}

	function compute(int $value): int {
		$base = $value;
		$next = $base + 1;
		return $next;
	}
}

namespace StageTwo\Exec\Inner {
	class Token {
	}
}

namespace StageTwo\Exec {
	$a = Worker::make();
	$b = compute($a);
	$c = $b;
	$c = compute($c);
	$d = new Worker();
	$e = $d::make();

	echo $c, "|", $e, "\n";
}
