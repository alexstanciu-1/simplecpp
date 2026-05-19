#include "test_common.hpp"

#include "lang/php/support/php_mysqli.hpp"

namespace {

class stub_result_handle final : public scpp::db::mysql_module::result_handle {
public:
	explicit stub_result_handle(int rows_before_end)
		: rows_remaining_(rows_before_end),
		  initial_rows_(rows_before_end) {
	}

	[[nodiscard]] std::int64_t num_rows() const override {
		return initial_rows_;
	}

	[[nodiscard]] scpp::dynamic_t<> fetch_row() override {
		return fetch_one(false);
	}

	[[nodiscard]] scpp::dynamic_t<> fetch_assoc() override {
		return fetch_one(true);
	}

	[[nodiscard]] bool has_error() const override {
		return false;
	}

	[[nodiscard]] scpp::db::mysql_module::status last_status() const override {
		return {};
	}

private:
	int rows_remaining_ = 0;
	int initial_rows_ = 0;

	[[nodiscard]] scpp::dynamic_t<> fetch_one(bool associative) {
		if (rows_remaining_ <= 0) {
			return scpp::dynamic_t<>(scpp::null_t{});
		}

		--rows_remaining_;
		scpp::hash_t<scpp::mixed_t> out;
		if (associative) {
			out.set(scpp::string_t("name"), scpp::mixed_t(scpp::string_t("row")));
		} else {
			static_cast<void>(out.append(scpp::mixed_t(scpp::string_t("row"))));
		}
		return scpp::to_dynamic(out);
	}
};

static void test_fetch_assoc_terminates_with_null() {
	auto handle = std::make_shared<stub_result_handle>(2);
	scpp::mysqli_result result(handle);

	auto first = result.fetch_assoc();
	auto second = result.fetch_assoc();
	auto exhausted = result.fetch_assoc();

	assert(static_cast<bool>(first));
	assert(static_cast<bool>(second));
	assert(!static_cast<bool>(exhausted));
	assert(exhausted == scpp::null_t{});
}

static void test_fetch_row_terminates_with_null() {
	auto handle = std::make_shared<stub_result_handle>(1);
	scpp::mysqli_result result(handle);

	auto first = result.fetch_row();
	auto exhausted = result.fetch_row();

	assert(static_cast<bool>(first));
	assert(!static_cast<bool>(exhausted));
	assert(exhausted == scpp::null_t{});
}

static void test_null_handle_fetches_are_null() {
	scpp::mysqli_result result(nullptr);

	auto assoc = result.fetch_assoc();
	auto row = result.fetch_row();

	assert(!static_cast<bool>(assoc));
	assert(!static_cast<bool>(row));
	assert(assoc == scpp::null_t{});
	assert(row == scpp::null_t{});
}

} // namespace

int main() {
	test_fetch_assoc_terminates_with_null();
	test_fetch_row_terminates_with_null();
	test_null_handle_fetches_are_null();
	return 0;
}
