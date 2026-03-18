#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-STR-05.
// Expected compile failure:
// RT-STR-05 Implicit numeric concatenation must remain unavailable.
// This must fail because string_t + int_t is not a valid overload.

int main() {
	using namespace scpp;
	auto x = string_t("n=") + int_t(5);
	(void)x;
	return 0;
}
