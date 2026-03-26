#include "test_common.hpp"

namespace {

static void test_native_reference_aliases_same_target() {
	scpp::int_t value(10);
	scpp::int_t &first = value;
	scpp::int_t &second = first;

	second = scpp::int_t(12);
	assert(value.native_value() == 12);
	assert(first.native_value() == 12);
	assert(second.native_value() == 12);
}

static void test_native_reference_assignment_writes_through_without_rebind() {
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
}

} // namespace

int main() {
	test_native_reference_aliases_same_target();
	test_native_reference_assignment_writes_through_without_rebind();
	return 0;
}
