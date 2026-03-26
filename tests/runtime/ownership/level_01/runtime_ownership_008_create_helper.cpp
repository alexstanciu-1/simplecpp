#include "tests/runtime/runtime_test_common.hpp"

int main() {
	auto created = scpp::create<runtime_test::sample_object>(scpp::int_t(33));
	assert(created.has_value().native_value() == true);
	assert(created->value.native_value() == 33);
	assert(created.use_count() == 1U);

	auto observer = scpp::weak(created);
	assert(observer.expired().native_value() == false);
	assert(observer.lock()->value.native_value() == 33);
	return 0;
}
