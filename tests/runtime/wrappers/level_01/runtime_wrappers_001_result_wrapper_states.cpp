#include "tests/runtime/runtime_test_common.hpp"

namespace {

struct sample_box final {
	scpp::int_t<> value;

	explicit sample_box(scpp::int_t<> initial_value)
		: value(std::move(initial_value)) {
	}

	[[nodiscard]] scpp::int_t<> read() const {
		return value;
	}
};

} // namespace

int main() {
	scpp::result_or_false<scpp::int_t<>> value(scpp::int_t<>(10));
	scpp::result_or_false<scpp::int_t<>> fail(scpp::false_sentinel);
	assert(value.has_value().native_value() == true);
	assert(fail.is_false().native_value() == true);
	assert((fail == scpp::bool_t(false)).native_value() == true);
	assert(value.require_value("value expected").native_value() == 10);
	runtime_test::expect_throw<std::runtime_error>([&]() {
		(void) fail.require_value("fail should throw");
	});

	scpp::result_or_bool<scpp::int_t<>> bool_true(scpp::bool_t(true));
	scpp::result_or_bool<scpp::int_t<>> bool_false(scpp::false_sentinel);
	scpp::result_or_bool<scpp::int_t<>> wrapped_value(scpp::int_t<>(33));
	assert(bool_true.is_true().native_value() == true);
	assert(bool_false.is_false().native_value() == true);
	assert(wrapped_value.has_value().native_value() == true);
	assert((bool_false == scpp::false_sentinel).native_value() == true);
	runtime_test::expect_throw<std::runtime_error>([&]() {
		(void) bool_true.require_value("bool state is not a wrapped value");
	});

	scpp::result<sample_box> ok(sample_box(scpp::int_t<>(7)));
	scpp::result<sample_box> err(scpp::error_t(scpp::string_t("boom"), scpp::int_t<>(12), scpp::string_t("sample.php")));
	assert(ok.has_value().native_value() == true);
	assert(err.has_error().native_value() == true);
	assert(ok.require_value("expected value").read().native_value() == 7);
	assert(err.require_error("expected error").get_line().native_value() == 12);
	runtime_test::expect_throw<std::runtime_error>([&]() {
		(void) err.require_value("error state should throw");
	});

	return 0;
}
