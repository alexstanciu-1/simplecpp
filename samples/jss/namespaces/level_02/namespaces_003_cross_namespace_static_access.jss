namespace A;

class Box {
	static value(): int {
		return 5;
	}
}

namespace B;

print(A.Box.value(), "\n");
