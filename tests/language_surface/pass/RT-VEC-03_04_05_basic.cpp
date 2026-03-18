#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-VEC-03, RT-VEC-04, and RT-VEC-05.
// Primary requirements:
// - RT-VEC-03 Indexing must be supported.
// - RT-VEC-04 Append must be supported.
// - RT-VEC-05 Minimal v1 vector behavior is acceptable if it stays within the spec.

int main() {
	using namespace scpp;

	vector_t<int_t> v;
	v.append(int_t(10));
	v.append(int_t(20));

	if (v.size() != 2) {
		return 1;
	}
	if (v[0].native_value() != 10) {
		return 2;
	}
	if (v[1].native_value() != 20) {
		return 3;
	}

	return 0;
}
