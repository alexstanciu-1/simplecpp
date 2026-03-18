#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-NBL-01.
// Primary requirement:
// - RT-NBL-01 nullable<T> must wrap optional-like storage and integrate with null_t.

int main() {
	using namespace scpp;

	nullable<int_t> value = int_t(7);
	if (!value.has_value()) {
		return 1;
	}
	if (value.value().native_value() != 7) {
		return 2;
	}

	nullable<int_t> empty = null;
	if (empty.has_value()) {
		return 3;
	}

	return 0;
}
