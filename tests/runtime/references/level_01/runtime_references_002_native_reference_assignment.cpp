#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::int_t left(11);
	scpp::int_t right(20);
	scpp::int_t &target = left;
	scpp::int_t &source = right;

	auto *target_before = std::addressof(target);
	auto *source_before = std::addressof(source);

	target = source;
	assert(std::addressof(target) == target_before);
	assert(std::addressof(source) == source_before);
	assert(target_before != source_before);
	assert(left.native_value() == 20);
	assert(right.native_value() == 20);

	target = scpp::int_t(31);
	assert(left.native_value() == 31);
	assert(right.native_value() == 20);
	return 0;
}
