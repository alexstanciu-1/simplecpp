#include <scpp/runtime.hpp>
#include <type_traits>
#include <utility>

// Coverage marker: covered primary RT-UQ-01 and RT-UQ-02.
// Test-only host helper.
// Uses scpp-visible field types, but this helper itself exists only to validate runtime move/ownership mechanics.
struct Demo {
	scpp::int_t value;
	explicit Demo(scpp::int_t v) : value(v) {
	}
};

int main() {
	using namespace scpp;

	static_assert(std::is_move_constructible_v<unique_p<Demo>>);
	static_assert(!std::is_copy_constructible_v<unique_p<Demo>>);

	auto owner = unique<Demo>(int_t(5));
	unique_p<Demo> moved = std::move(owner);

	if (!(owner == null)) {
		return 1;
	}
	if (!(moved != null)) {
		return 2;
	}

	return 0;
}
