#include <scpp/runtime.hpp>

// Primary requirements:
// - RT-NULL-01 The runtime must provide a public null_t type and a public constant null.
// - RT-NULL-02 null_t must represent both pointer-null and optional-empty semantics.
// - RT-NULL-06 null_t == null_t and null_t != null_t must behave exactly as specified.

int main() {
	using namespace scpp;

	if (!(null == null)) {
		return 1;
	}
	if (null != null) {
		return 2;
	}

	nullable<int_t> maybe = null;
	if (maybe != null) {
		return 3;
	}

	shared_p<int> ptr = null;
	if (ptr != null) {
		return 4;
	}

	return 0;
}
