#include <scpp/runtime.hpp>

// Expected compile failure:
// RT-NULL-04 null_t must not implicitly convert to primitive wrappers.

int main() {
	using namespace scpp;
	int_t x = null;
	(void)x;
	return 0;
}
