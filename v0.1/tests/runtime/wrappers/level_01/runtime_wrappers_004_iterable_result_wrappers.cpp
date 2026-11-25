#include "tests/runtime/runtime_test_common.hpp"

#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/result.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"

int main() {
	scpp::hash_t<scpp::mixed_t> files;
	(void) files.append(scpp::mixed_t(scpp::string_t("alpha.txt")));
	(void) files.append(scpp::mixed_t(scpp::string_t("beta.txt")));

	scpp::result_or_false<scpp::hash_t<scpp::mixed_t>> or_false(files);
	assert(or_false.size() == 2);
	assert(or_false.at(scpp::int_t<>(0)).get_string().native_value() == "alpha.txt");
	assert(or_false.at(scpp::int_t<>(1)).get_string().native_value() == "beta.txt");

	std::size_t iterated_false = 0;
	for (auto it = or_false.begin_entries(); it != or_false.end_entries(); ++it) {
		const auto entry = *it;
		if (iterated_false == 0) {
			assert(entry.value_copy().get_string().native_value() == "alpha.txt");
		}
		++iterated_false;
	}
	assert(iterated_false == 2);

	or_false = scpp::false_sentinel;
	runtime_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(or_false.size());
	});

	scpp::result<scpp::hash_t<scpp::mixed_t>> ok_value(files);
	assert(ok_value.size() == 2);
	assert(ok_value.at(scpp::int_t<>(0)).get_string().native_value() == "alpha.txt");

	ok_value = scpp::error_t(scpp::string_t("boom"), scpp::int_t<>(5), scpp::string_t("iter.php"));
	runtime_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(ok_value.at(scpp::int_t<>(0)));
	});

	scpp::result_or_bool<scpp::hash_t<scpp::mixed_t>> or_bool(files);
	assert(or_bool.size() == 2);
	assert(or_bool.at(scpp::int_t<>(1)).get_string().native_value() == "beta.txt");

	or_bool = scpp::false_sentinel;
	runtime_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(or_bool.begin_entries());
	});

	return 0;
}
