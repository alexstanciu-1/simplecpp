#include "tests/runtime/runtime_test_common.hpp"

#include <iostream>

int main() {
	scpp::mixed_t int_value = scpp::int_t<>(4);
	int_value += scpp::mixed_t(scpp::int_t<>(3));
	assert(int_value.int_value().native_value() == 7);

	scpp::mixed_t float_value = scpp::float_t(1.5);
	float_value += scpp::mixed_t(scpp::int_t<>(2));
	assert(float_value.float_value().native_value() == 3.5);
	for (const char symbol : {'+', '-', '*', '/'}) {
		scpp::mixed_t target{scpp::int_t<>{4LL}};
		const scpp::mixed_t right{scpp::float_t{2.0}};
		runtime_test::expect_throw<std::runtime_error>([&]() {
			switch (symbol) {
				case '+': target += right; break;
				case '-': target -= right; break;
				case '*': target *= right; break;
				case '/': target /= right; break;
			}
		});
		assert(target.kind() == scpp::mixed_t::kind_t::int_v);
		assert(target.int_value().native_value() == 4);
	}
	// Ordinary assignment still replaces the active kind.
	scpp::mixed_t replacement{scpp::int_t<>{4LL}};
	replacement = replacement + scpp::mixed_t{scpp::float_t{0.5}};
	assert(replacement.float_value().native_value() == 4.5);
	// Reject a nonnumeric postfix target before attempting to clone its absent payload.
	scpp::mixed_t empty_owned{scpp::unique_p<scpp::hash_t<scpp::mixed_t>>{scpp::null}};
	runtime_test::expect_throw<std::runtime_error>([&]() { empty_owned++; });
	runtime_test::expect_throw<std::runtime_error>([&]() { empty_owned--; });
	assert(empty_owned.table_if() == nullptr);

	scpp::mixed_t string_value = scpp::string_t("ab");
	runtime_test::expect_throw<std::runtime_error>([&]() {
		string_value += scpp::mixed_t(scpp::string_t("cd"));
	});

	scpp::mixed_t inc_value = scpp::int_t<>(10);
	++inc_value;
	assert(inc_value.int_value().native_value() == 11);

	scpp::mixed_t dec_value = scpp::float_t(4.5);
	dec_value--;
	assert(dec_value.float_value().native_value() == 3.5);

	assert((!scpp::mixed_t(scpp::int_t<>(0))).native_value() == true);
	assert((scpp::mixed_t(scpp::int_t<>(1)) && scpp::mixed_t(scpp::float_t(2.0))).native_value() == true);
	assert((scpp::mixed_t(scpp::float_t(0.0)) || scpp::mixed_t(scpp::bool_t(true))).native_value() == true);
	runtime_test::expect_throw<scpp::runtime_error>([&]() {
		(void)(scpp::mixed_t(scpp::int_t<>(10)) / scpp::mixed_t(scpp::int_t<>(0)));
	});
	runtime_test::expect_throw<scpp::runtime_error>([&]() {
		(void)(scpp::mixed_t(scpp::int_t<>(10)) % scpp::mixed_t(scpp::int_t<>(0)));
	});

	std::cout << "compound_unary_logical_ok\n";
	return 0;
}
