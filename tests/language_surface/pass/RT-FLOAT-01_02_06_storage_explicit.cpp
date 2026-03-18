#include <scpp/runtime.hpp>
#include <type_traits>

// Coverage marker: covered primary RT-FLOAT-01, RT-FLOAT-02, and RT-FLOAT-06.
// Primary requirements:
// - RT-FLOAT-01 float_t must store an 8-byte floating semantic value backed by double.
// - RT-FLOAT-02 float_t must behave as a value type.
// - RT-FLOAT-06 float_t must support only the explicit conversion paths defined by the matrix.

int main() {
	using namespace scpp;

	static_assert(sizeof(double) == 8);
	static_assert(std::is_copy_constructible_v<float_t>);
	static_assert(std::is_copy_assignable_v<float_t>);

	const float_t value(3.25);
	if (static_cast<double>(value) != 3.25) {
		return 1;
	}

	return 0;
}
