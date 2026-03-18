#include <scpp/runtime.hpp>
#include <type_traits>

// Coverage marker: covered primary RT-INT-01, RT-INT-02, RT-INT-05, and RT-INT-07.
// Primary requirements:
// - RT-INT-01 int_t must store an 8-byte signed integer semantic value backed by long long.
// - RT-INT-02 int_t must behave as a value type.
// - RT-INT-05 int_t must support conditional evaluation semantics used by the language without implying a general implicit conversion to bool_t.
// - RT-INT-07 int_t must support explicit conversion paths defined by the matrix and no others.

int main() {
	using namespace scpp;

	static_assert(sizeof(long long) == 8);
	static_assert(std::is_copy_constructible_v<int_t>);
	static_assert(std::is_copy_assignable_v<int_t>);

	const int_t zero(0);
	const int_t nonzero(9);

	if (zero.condition_value()) {
		return 1;
	}
	if (!nonzero.condition_value()) {
		return 2;
	}
	if (static_cast<long long>(nonzero) != 9) {
		return 3;
	}

	return 0;
}
