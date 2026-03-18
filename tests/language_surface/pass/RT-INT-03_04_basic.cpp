#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-INT-03 and RT-INT-04.
// Primary requirements:
// - RT-INT-03 int_t must support arithmetic operators against int_t.
// - RT-INT-04 int_t must support comparison operators against int_t.

int main() {
	using namespace scpp;

	const int_t a(10);
	const int_t b(3);

	if ((a + b).native_value() != 13) {
		return 1;
	}
	if ((a - b).native_value() != 7) {
		return 2;
	}
	if ((a * b).native_value() != 30) {
		return 3;
	}
	if ((a / b).native_value() != 3) {
		return 4;
	}
	if (!(a > b)) {
		return 5;
	}
	if (!(a >= b)) {
		return 6;
	}
	if (!(b < a)) {
		return 7;
	}
	if (!(b <= a)) {
		return 8;
	}

	return 0;
}
