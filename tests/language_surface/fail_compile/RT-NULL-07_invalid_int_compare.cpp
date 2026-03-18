#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-NULL-07.
// Expected compile failure:
// - RT-NULL-07 comparison with null must be available only where permitted by the specs.
// This must fail because primitive wrappers must not compare directly with null.

int main() {
	using namespace scpp;
	return int_t(1) == null;
}
