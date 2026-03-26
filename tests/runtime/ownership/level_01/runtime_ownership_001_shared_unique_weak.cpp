#include "tests/runtime/runtime_test_common.hpp"

int main() {
	const auto shared = scpp::shared<runtime_test::sample_object>(scpp::int_t(12));
	assert(shared.has_value().native_value() == true);
	assert(shared->value.native_value() == 12);

	const auto shared_copy = shared;
	shared_copy->value = scpp::int_t(44);
	assert(shared->value.native_value() == 44);
	assert((shared == shared_copy).native_value() == true);

	auto unique = scpp::unique<runtime_test::sample_object>(scpp::int_t(9));
	assert(unique.has_value().native_value() == true);
	assert(unique->value.native_value() == 9);

	auto moved = std::move(unique);
	assert((unique == scpp::null).native_value() == true);
	assert((moved != scpp::null).native_value() == true);
	assert(moved->value.native_value() == 9);

	auto owner = scpp::shared<runtime_test::sample_object>(scpp::int_t(77));
	const auto observer = scpp::weak(owner);
	assert(observer.expired().native_value() == false);
	assert(observer.lock()->value.native_value() == 77);
	owner = scpp::shared_p<runtime_test::sample_object>(scpp::null);
	assert(observer.expired().native_value() == true);
	assert(observer.lock().has_value().native_value() == false);
	return 0;
}
