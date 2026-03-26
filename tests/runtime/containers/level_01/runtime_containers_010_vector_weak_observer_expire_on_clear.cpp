#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::weak_p<runtime_test::sample_object> observer;
	{
		scpp::vector_t<scpp::shared_p<runtime_test::sample_object>> values;
		values.append(scpp::shared<runtime_test::sample_object>(scpp::int_t(40)));
		observer = scpp::weak(values.at(0));
		assert(observer.expired().native_value() == false);
		values.clear();
		assert(observer.expired().native_value() == true);
	}
	assert(observer.lock().has_value().native_value() == false);
	return 0;
}
