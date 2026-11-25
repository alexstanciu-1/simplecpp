namespace A;

function value(): int {
	return 8;
}

namespace B;

use function A.value;

print(value(), "\n");
