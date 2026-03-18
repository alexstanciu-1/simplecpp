#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-NBL-02, RT-NBL-03, RT-NBL-04, and RT-NBL-05.
// Primary requirements:
// - RT-NBL-02 nullable<T> must support construction from null and from T.
// - RT-NBL-03 nullable<T> must expose a clear empty/non-empty state.
// - RT-NBL-04 nullable<T> == nullable<T> must compare as specified.
// - RT-NBL-05 nullable<T> == null and != null must test empty state.

int main() {
	using namespace scpp;

	nullable<int_t> empty = null;
	nullable<int_t> a = int_t(5);
	nullable<int_t> b = int_t(5);
	nullable<int_t> c = int_t(9);

	if (empty.has_value()) {
		return 1;
	}
	if (a == null) {
		return 2;
	}
	if (!(empty == null)) {
		return 3;
	}
	if (!(a == b)) {
		return 4;
	}
	if (!(a != c)) {
		return 5;
	}

	return 0;
}
