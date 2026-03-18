#include <scpp/runtime.hpp>
#include <optional>

// Coverage marker: covered primary RT-NULL-03.
// Primary requirement:
// - RT-NULL-03 null_t may interoperate with std::nullptr_t and std::nullopt_t internally or at the generated-code boundary only as required by the specs.

int main() {
	using namespace scpp;

	std::nullptr_t p = null;
	std::nullopt_t o = null;
	(void)p;
	(void)o;

	return 0;
}
