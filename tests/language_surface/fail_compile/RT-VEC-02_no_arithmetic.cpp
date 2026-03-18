#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-VEC-02.
// Expected compile failure:
// - RT-VEC-02 arithmetic operators on vector_t<T> must remain unavailable.

int main() {
	using namespace scpp;
	vector_t<int_t> a;
	vector_t<int_t> b;
	auto c = a + b;
	(void)c;
	return 0;
}
