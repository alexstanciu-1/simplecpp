#include <scpp/runtime.hpp>

// Expected compile failure:
// RT-BOOL-06 Arithmetic on bool_t must not be exposed.
// This must fail because no bool_t arithmetic overload should exist.

int main() {
	using namespace scpp;
	auto x = bool_t(true) + bool_t(false);
	(void)x;
	return 0;
}
