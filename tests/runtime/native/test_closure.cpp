#include "test_common.hpp"

static void test_basic_closure_callable() {
	std::function<scpp::int_t(scpp::int_t, scpp::int_t)> closure = [factor = scpp::int_t(3)](scpp::int_t x, scpp::int_t y) {
		return factor * (x + y);
	};

	auto result = closure(scpp::int_t(2), scpp::int_t(4));
	assert(result.native_value() == 18);
	assert(static_cast<bool>(closure) == true);
}

static void test_capture_by_value_is_stable() {
	scpp::int_t w(1);
	std::function<scpp::int_t()> closure = [w]() {
		return w;
	};

	w = scpp::int_t(9);
	assert(closure().native_value() == 1);
}

static void test_direct_invocation_exact_arity() {
	std::function<scpp::string_t()> x = []() {
		return scpp::string_t("ok x");
	};
	std::function<scpp::int_t(scpp::int_t)> y = [](scpp::int_t a) {
		return a + scpp::int_t(1);
	};

	auto x_result = x();
	auto y_result = y(scpp::int_t(10));
	assert(x_result.native_value() == "ok x");
	assert(y_result.native_value() == 11);
}

static void test_direct_invocation_by_reference_parameter() {
	scpp::int_t value(3);
	std::function<void(scpp::int_t&)> q = [](scpp::int_t& x) {
		x = x + scpp::int_t(1);
	};

	q(value);
	assert(value.native_value() == 4);
}

int main() {
	test_basic_closure_callable();
	test_capture_by_value_is_stable();
	test_direct_invocation_exact_arity();
	test_direct_invocation_by_reference_parameter();
	return 0;
}
