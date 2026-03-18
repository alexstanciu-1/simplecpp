#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-INT-06 and RT-FLOAT-04.
// Primary requirements:
// - RT-INT-06 int_t must support the implicit int -> float path.
// - RT-FLOAT-04 Mixed int_t / float_t arithmetic and comparison must promote the int_t side to float_t semantics.

int main() {
	using namespace scpp;

	const int_t i(2);
	const float_t f(0.5);

	if ((i + f).native_value() != 2.5) {
		return 1;
	}
	if ((f + i).native_value() != 2.5) {
		return 2;
	}
	if ((i * f).native_value() != 1.0) {
		return 3;
	}
	if (!(i > float_t(1.5))) {
		return 4;
	}
	if (!(float_t(2.0) == i)) {
		return 5;
	}

	return 0;
}
