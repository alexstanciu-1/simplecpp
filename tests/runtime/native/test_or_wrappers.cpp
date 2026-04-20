#include "test_common.hpp"

static void test_or_false_basic() {
	scpp::result_or_false<scpp::int_t> value(scpp::int_t(10));
	scpp::result_or_false<scpp::int_t> fail(scpp::false_sentinel);

	assert(value.has_value().native_value() == true);
	assert((fail == scpp::false_sentinel).native_value() == true);
	assert((fail == scpp::bool_t(false)).native_value() == true);
	assert((value + scpp::int_t(2)).native_value() == 12);
	assert(scpp::cast<scpp::int_t>(value).native_value() == 10);

	bool threw = false;
	try {
		(void)scpp::cast<scpp::int_t>(fail);
	} catch (const std::runtime_error &) {
		threw = true;
	}
	assert(threw == true);

	scpp::result_or_false<scpp::bool_t> bool_payload_false(scpp::bool_t(false));
	scpp::result_or_false<scpp::bool_t> bool_sentinel_false(scpp::false_sentinel);
	assert(bool_payload_false.has_value().native_value() == true);
	assert(bool_payload_false.value().native_value() == false);
	assert(bool_sentinel_false.has_value().native_value() == false);
	assert((bool_sentinel_false == scpp::false_sentinel).native_value() == true);
}

struct sample_box final {
	scpp::int_t value = static_cast<scpp::int_t>(7);
	[[nodiscard]] scpp::int_t get_value() const { return value; }
};


static void test_result_or_bool_bool_payload_policy() {
	scpp::result_or_bool<scpp::bool_t> payload_false(scpp::bool_t(false));
	scpp::result_or_bool<scpp::bool_t> payload_true(true);
	scpp::result_or_bool<scpp::bool_t> sentinel_true(scpp::true_sentinel);
	scpp::result_or_bool<scpp::bool_t> sentinel_false(scpp::false_sentinel);

	assert(payload_false.has_value().native_value());
	assert(!payload_false.is_false().native_value());
	assert(!payload_false.is_true().native_value());
	assert(!payload_false.value().native_value());

	assert(payload_true.has_value().native_value());
	assert(payload_true.value().native_value());

	assert(!sentinel_true.has_value().native_value());
	assert(sentinel_true.is_true().native_value());
	assert(!sentinel_true.is_false().native_value());

	assert(!sentinel_false.has_value().native_value());
	assert(!sentinel_false.is_true().native_value());
	assert(sentinel_false.is_false().native_value());
}

static void test_result_or_bool_basic() {
	scpp::result_or_bool<scpp::int_t> value(scpp::int_t(10));
	scpp::result_or_bool<scpp::int_t> bool_true(scpp::bool_t(true));
	scpp::result_or_bool<scpp::int_t> bool_false(scpp::false_sentinel);

	assert(value.has_value().native_value() == true);
	assert((bool_true == scpp::bool_t(true)).native_value() == true);
	assert((bool_false == scpp::bool_t(false)).native_value() == true);
	assert((bool_false == scpp::false_sentinel).native_value() == true);

	bool threw = false;
	try {
		(void)scpp::cast<scpp::int_t>(bool_true);
	} catch (const std::runtime_error &) {
		threw = true;
	}
	assert(threw == true);
}

static void test_or_error_basic() {
	scpp::result<sample_box> ok(sample_box{});
	scpp::result<sample_box> err(scpp::error_t(scpp::string_t("boom"), static_cast<scpp::int_t>(12), scpp::string_t("file.php")));

	assert(ok.has_value().native_value() == true);
	assert((err == scpp::error).native_value() == true);
	assert(ok->get_value().native_value() == 7);
	assert(std::string(err.error()->get_message().native_value()) == "boom");
	assert(err.error()->get_line().native_value() == 12);
	assert(std::string(err.error()->get_file().native_value()) == "file.php");

	auto takes_box = [](sample_box value) {
		return value.get_value().native_value();
	};
	assert(takes_box(ok) == 7);

	bool threw = false;
	try {
		(void)takes_box(err);
	} catch (const std::runtime_error &) {
		threw = true;
	}
	assert(threw == true);

	threw = false;
	try {
		(void)err->get_value();
	} catch (const std::runtime_error &) {
		threw = true;
	}
	assert(threw == true);
}

int main() {
	test_or_false_basic();
	test_result_or_bool_bool_payload_policy();
	test_result_or_bool_basic();
	test_or_error_basic();
	return 0;
}
