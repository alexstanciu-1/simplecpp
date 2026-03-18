#include <scpp/runtime.hpp>
#include <type_traits>

// Coverage marker: covered primary RT-MEM-05 and partial RT-MEM-06 / RT-MEM-08.
// Test-only host helper.
// This helper exists only to validate runtime ownership helper mechanics, not generated-language surface rules.
struct Demo {
	scpp::int_t value;
	explicit Demo(scpp::int_t v) : value(v) {
	}
};

int main() {
	using namespace scpp;

	auto owner = shared<Demo>(int_t(1));
	static_assert(std::is_same_v<decltype(weak(owner)), weak_p<Demo>>);

	auto w1 = weak(owner);
	auto w2 = weak(owner);

	if (!(w1 == w2)) {
		return 1;
	}
	if (!(w1 != null)) {
		return 2;
	}

	return 0;
}
