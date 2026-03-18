#include <scpp/runtime.hpp>

// Primary requirement:
// - RT-FLOAT-03 float_t must support arithmetic operators required by CASTING.md.

int main() {
	using namespace scpp;

	const float_t a(7.5);
	const float_t b(2.5);

	if ((a + b).native_value() != 10.0) {
		return 1;
	}
	if ((a - b).native_value() != 5.0) {
		return 2;
	}
	if ((a * b).native_value() != 18.75) {
		return 3;
	}
	if ((a / b).native_value() != 3.0) {
		return 4;
	}

	return 0;
}
