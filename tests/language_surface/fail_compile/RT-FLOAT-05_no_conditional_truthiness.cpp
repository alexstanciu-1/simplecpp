#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-FLOAT-05.
// Expected compile failure:
// - RT-FLOAT-05 float_t is not valid in conditional expressions by default under the language rules.

int main() {
	using namespace scpp;
	if (float_t(1.0)) {
		return 1;
	}
	return 0;
}
