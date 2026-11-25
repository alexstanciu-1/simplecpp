namespace A;

class Box {
	static value(): int {
		return 37;
	}
}

namespace B;

print(A.Box.value(), "\n");
