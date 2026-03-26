#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto value = scpp::unique<runtime_test::sample_object>(scpp::int_t(8));
	assert(value.has_value().native_value() == true);
	assert(value->value.native_value() == 8);

	auto moved = std::move(value);
	assert((value == scpp::null).native_value() == true);
	assert(moved->value.native_value() == 8);

	auto *raw = moved.release();
	assert(raw != nullptr);
	assert((moved == scpp::null_ptr).native_value() == true);
	assert(raw->value.native_value() == 8);
	delete raw;

	moved.reset(new runtime_test::sample_object(scpp::int_t(13)));
	assert(moved->value.native_value() == 13);
	moved.reset();
	assert((moved == scpp::null).native_value() == true);
	return 0;
}
