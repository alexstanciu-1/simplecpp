#include <scpp/runtime.hpp>
#include <stdexcept>

// Coverage marker: covered primary RT-NBL-06 runtime-null-path.
// Primary requirement:
// - RT-NBL-06 relational operators on nullable<T> must be available only when both sides are non-null and T supports the operation.
// This runtime-mechanics test documents the current implementation choice: null-side relational checks throw at runtime.

int main() {
	using namespace scpp;

	const nullable<int_t> empty = null;
	const nullable<int_t> value = int_t(1);

	try {
		(void)(empty < value);
		return 1;
	} catch (const std::runtime_error &) {
	}

	return 0;
}
