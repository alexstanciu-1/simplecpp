#include "test_common.hpp"

// Verifies the current configured rule that all sentinel families compare equal.
static void test_cross_sentinel_equality() {
	assert((scpp::null == scpp::null).native_value() == true);
	assert((scpp::nullopt == scpp::nullopt).native_value() == true);
	assert((scpp::null_ptr == scpp::null_ptr).native_value() == true);

	assert((scpp::null == scpp::nullopt).native_value() == true);
	assert((scpp::nullopt == scpp::null).native_value() == true);
	assert((scpp::null == scpp::null_ptr).native_value() == true);
	assert((scpp::null_ptr == scpp::null).native_value() == true);
	assert((scpp::nullopt == scpp::null_ptr).native_value() == true);
	assert((scpp::null_ptr == scpp::nullopt).native_value() == true);
}

// Verifies the paired inequality operators remain the logical inverse of equality.
static void test_cross_sentinel_inequality() {
	assert((scpp::null != scpp::null).native_value() == false);
	assert((scpp::nullopt != scpp::nullopt).native_value() == false);
	assert((scpp::null_ptr != scpp::null_ptr).native_value() == false);

	assert((scpp::null != scpp::nullopt).native_value() == false);
	assert((scpp::nullopt != scpp::null).native_value() == false);
	assert((scpp::null != scpp::null_ptr).native_value() == false);
	assert((scpp::null_ptr != scpp::null).native_value() == false);
	assert((scpp::nullopt != scpp::null_ptr).native_value() == false);
	assert((scpp::null_ptr != scpp::nullopt).native_value() == false);
}

int main() {
	test_cross_sentinel_equality();
	test_cross_sentinel_inequality();
	return 0;
}
