#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-BOOL-02 and RT-BOOL-05.
// Primary requirements:
// - RT-BOOL-02 bool_t must support explicit construction from native boolean input for runtime entry.
// - RT-BOOL-05 bool_t must support the implicit bool -> int path through the runtime-visible wrapper model.

int main() {
	using namespace scpp;

	const bool_t t(true);
	const bool_t f(false);
	const int_t ti = t;
	const int_t fi = f;

	if (ti.native_value() != 1) {
		return 1;
	}
	if (fi.native_value() != 0) {
		return 2;
	}

	return 0;
}
