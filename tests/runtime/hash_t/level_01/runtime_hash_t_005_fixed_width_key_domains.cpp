#include "tests/runtime/runtime_test_common.hpp"

#include <cstdint>
#include <iostream>
#include <type_traits>

int main() {
	scpp::hash_t<scpp::int_t<>, scpp::int_t<std::uint32_t>> by_u32;
	by_u32.set(scpp::int_t<std::uint32_t>(7), scpp::int_t<>(70));
	by_u32[scpp::int_t<std::uint32_t>(9)] = scpp::int_t<>(90);

	assert(by_u32.has(scpp::int_t<std::uint32_t>(7)).native_value() == true);
	assert(by_u32.has(scpp::int_t<std::uint32_t>(8)).native_value() == false);
	assert(by_u32.at(scpp::int_t<std::uint32_t>(7)).native_value() == 70);
	assert(by_u32.at(scpp::int_t<std::uint32_t>(9)).native_value() == 90);

	const auto u32_append_key = by_u32.append(scpp::int_t<>(100));
	static_assert(std::is_same_v<decltype(u32_append_key), const scpp::int_t<std::uint32_t>>);
	assert(u32_append_key.native_value() == 10);
	assert(by_u32.at(u32_append_key).native_value() == 100);

	scpp::hash_t<scpp::string_t, scpp::int_t<std::uint8_t>> by_byte;
	const auto byte_key_0 = by_byte.append(scpp::string_t("zero"));
	const auto byte_key_1 = by_byte.append(scpp::string_t("one"));
	static_assert(std::is_same_v<decltype(byte_key_0), const scpp::int_t<std::uint8_t>>);
	assert(byte_key_0.native_value() == static_cast<std::uint8_t>(0));
	assert(byte_key_1.native_value() == static_cast<std::uint8_t>(1));
	assert(by_byte.at(byte_key_0).native_value() == "zero");
	assert(by_byte.at(byte_key_1).native_value() == "one");

	by_byte.set(scpp::int_t<std::uint8_t>(255), scpp::string_t("max"));
	runtime_test::expect_throw<std::overflow_error>([&]() {
		(void) by_byte.append(scpp::string_t("overflow"));
	});

	std::cout << "fixed_width_key_domains_ok\n";
	return 0;
}
