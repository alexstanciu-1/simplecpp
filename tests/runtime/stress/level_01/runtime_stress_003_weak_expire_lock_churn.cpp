#include "tests/runtime/runtime_test_common.hpp"

int main() {
	for (int i = 0; i < 20000; ++i) {
		auto owner = scpp::shared<runtime_test::sample_object>(scpp::int_t(i));
		auto observer = scpp::weak(owner);
		assert(observer.expired().native_value() == false);
		{
			auto locked = observer.lock();
			assert(locked.has_value().native_value() == true);
			assert(locked->value.native_value() == i);
		}
		owner.reset();
		assert(observer.expired().native_value() == true);
		auto locked = observer.lock();
		assert(locked.has_value().native_value() == false);
	}
	return 0;
}
