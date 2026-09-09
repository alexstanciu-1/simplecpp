#include "test_common.hpp"

#include "scpp/bitset_t.hpp"

#include <cassert>

namespace {

void test_boundary_bits() {
	scpp::bitset_t bits(129);
	assert(bits.size() == 129U);
	assert(bits.word_count() == 3U);
	assert(!bits.any_set());
	assert(bits.count() == 0U);

	for (const auto index : {0U, 1U, 63U, 64U, 65U, 127U, 128U}) {
		bits.set(index);
		assert(bits.test(index));
	}

	assert(bits.any_set());
	assert(bits.count() == 7U);

	bits.clear(64U);
	bits.clear(128U);
	assert(!bits.test(64U));
	assert(!bits.test(128U));
	assert(bits.count() == 5U);
}

void test_resize_masks_tail_bits() {
	scpp::bitset_t bits(130);
	bits.set(0U);
	bits.set(64U);
	bits.set(129U);
	assert(bits.count() == 3U);

	bits.resize(65U);
	assert(bits.size() == 65U);
	assert(bits.word_count() == 2U);
	assert(bits.test(0U));
	assert(bits.test(64U));
	assert(bits.count() == 2U);

	bits.resize(130U);
	assert(bits.test(0U));
	assert(bits.test(64U));
	assert(!bits.test(129U));
	assert(bits.count() == 2U);
}

void test_clear_behavior() {
	scpp::bitset_t bits(70);
	bits.set(3U);
	bits.set(69U);
	assert(bits.any_set());

	bits.clear_all_bits();
	assert(!bits.any_set());
	assert(bits.count() == 0U);
	assert(bits.size() == 70U);

	bits.set(10U);
	bits.clear();
	assert(bits.size() == 0U);
	assert(bits.word_count() == 0U);
	assert(!bits.any_set());
}

void test_bounds_checks() {
	scpp::bitset_t bits(8);
	scpp_test::expect_throw<scpp::runtime_error>([&]() {
		bits.set(8U);
	});
	scpp_test::expect_throw<scpp::runtime_error>([&]() {
		bits.clear(8U);
	});
	scpp_test::expect_throw<scpp::runtime_error>([&]() {
		static_cast<void>(bits.test(8U));
	});
}

} // namespace

int main() {
	test_boundary_bits();
	test_resize_masks_tail_bits();
	test_clear_behavior();
	test_bounds_checks();
	return 0;
}
