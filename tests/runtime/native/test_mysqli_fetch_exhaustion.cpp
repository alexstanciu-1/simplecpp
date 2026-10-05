#include "test_common.hpp"

#include "lang/php/support/php_mysqli.hpp"

namespace {

void require(bool condition) {
	if (!condition) throw std::runtime_error("mysqli row contract check failed");
}

class stub_result_handle final : public scpp::db::mysql_module::result_handle {
public:
	explicit stub_result_handle(int rows_before_end)
		: rows_remaining_(rows_before_end),
		  initial_rows_(rows_before_end) {
	}

	[[nodiscard]] std::int64_t num_rows() const override {
		return initial_rows_;
	}

	[[nodiscard]] scpp::detail::dynamic_value fetch_row() override {
		return fetch_one(false);
	}

	[[nodiscard]] scpp::detail::dynamic_value fetch_assoc() override {
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

	[[nodiscard]] scpp::detail::dynamic_value fetch_one(bool associative) {
		if (rows_remaining_ <= 0) {
			return scpp::detail::dynamic_value(scpp::null_t{});
		}

		--rows_remaining_;
		auto out = scpp::detail::dynamic_value::make_container();
		if (associative) {
			out.get_table().set(scpp::string_t("name"), scpp::detail::dynamic_value(scpp::string_t("row")));
		} else {
			static_cast<void>(out.get_table().append(scpp::detail::dynamic_value(scpp::string_t("row"))));
		}
		return out;
	}
};

static void test_fetch_assoc_terminates_with_null() {
	auto handle = std::make_shared<stub_result_handle>(2);
	scpp::mysqli_result result(handle);

	auto first = result.fetch_assoc();
	auto second = result.fetch_assoc();
	auto exhausted = result.fetch_assoc();

	require(scpp::condition_truthy(first).native_value());
	require(scpp::condition_truthy(second).native_value());
	require(first.get_table().size() == 1);
	require(!scpp::condition_truthy(exhausted).native_value());
	require(exhausted.is_null().native_value());
	require(!first.get_table().is_packed().native_value());
	require(first.get_table().at(scpp::string_t{"name"}).get_string().native_value() == "row");
	auto alias = first;
	alias.get_table().set(scpp::string_t{"name"}, scpp::detail::dynamic_value{scpp::string_t{"changed"}});
	require(first.get_table().at(scpp::string_t{"name"}).get_string().native_value() == "changed");
	require(second.get_table().at(scpp::string_t{"name"}).get_string().native_value() == "row");
	alias = scpp::detail::dynamic_value{};
	require(!first.is_null().native_value());
}

static void test_fetch_row_terminates_with_null() {
	auto handle = std::make_shared<stub_result_handle>(1);
	scpp::mysqli_result result(handle);

	auto first = result.fetch_row();
	auto exhausted = result.fetch_row();

	require(scpp::condition_truthy(first).native_value());
	require(!scpp::condition_truthy(exhausted).native_value());
	require(exhausted.is_null().native_value());
	require(first.get_table().is_packed().native_value());
	require(first.get_table().at(scpp::int_t<>{0}).get_string().native_value() == "row");
}

static void test_null_handle_fetches_are_null() {
	scpp::mysqli_result result(nullptr);

	auto assoc = result.fetch_assoc();
	auto row = result.fetch_row();

	require(!scpp::condition_truthy(assoc).native_value());
	require(!scpp::condition_truthy(row).native_value());
	require(assoc.is_null().native_value());
	require(row.is_null().native_value());
}

} // namespace

int main() {
	test_fetch_assoc_terminates_with_null();
	test_fetch_row_terminates_with_null();
	test_null_handle_fetches_are_null();
	return 0;
}
