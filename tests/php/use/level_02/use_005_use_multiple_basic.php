<?php
declare(strict_types=1);

namespace A {
	class Box {
		public static function value(): int {
			return 1;
		}
	}
	function plus(int $a, int $b): int {
		return $a + $b;
	}
	const BASE = 5;
}

namespace B {
	use A\Box;
	use function A\plus;
	use const A\BASE;
	echo Box::value(), "
";
	echo plus(BASE, 2), "
";
}
