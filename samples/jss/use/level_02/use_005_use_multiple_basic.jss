namespace A;

class Box {
	static value(): int {
		return 1;
	}
}

function plus(a: int, b: int): int {
	return a + b;
}

const BASE = 5;

namespace B;

use A.Box;
use function A.plus;
use const A.BASE;

print(Box.value(), "\n");
print(plus(BASE, 2), "\n");
