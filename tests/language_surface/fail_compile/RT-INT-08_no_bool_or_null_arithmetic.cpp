#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-INT-08.
// Expected compile failure:
// - RT-INT-08 arithmetic with bool_t, null_t, and pointer-like types must remain unavailable.

int main() {
	using namespace scpp;
	auto a = int_t(1) + bool_t(true);
	auto b = int_t(1) + null;
	(void)a;
	(void)b;
	return 0;
}
