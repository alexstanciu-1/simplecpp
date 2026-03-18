#include <scpp/runtime.hpp>

// Primary requirement:
// - RT-NBL-06 Relational operators on nullable<T> must be available only when both sides are non-null and T supports the operation.

int main() {
	using namespace scpp;

	const nullable<int_t> a = int_t(1);
	const nullable<int_t> b = int_t(2);

	if (!(a < b)) {
		return 1;
	}
	if (!(a <= b)) {
		return 2;
	}
	if (!(b > a)) {
		return 3;
	}
	if (!(b >= a)) {
		return 4;
	}

	return 0;
}
