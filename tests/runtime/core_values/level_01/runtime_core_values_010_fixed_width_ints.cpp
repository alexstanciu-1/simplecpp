#include "tests/runtime/runtime_test_common.hpp"

#include <cstdint>
#include <type_traits>

int main() {
	static_assert(sizeof(scpp::int_t<std::int8_t>) == sizeof(std::int8_t));
	static_assert(sizeof(scpp::int_t<std::uint8_t>) == sizeof(std::uint8_t));
	static_assert(sizeof(scpp::int_t<std::int16_t>) == sizeof(std::int16_t));
	static_assert(sizeof(scpp::int_t<std::uint32_t>) == sizeof(std::uint32_t));

	scpp::int_t<std::int8_t> small(120);
	scpp::int_t<std::int8_t> delta(10);
	auto wrapped = small + delta;
	static_assert(std::is_same_v<decltype(wrapped), scpp::int_t<std::int8_t>>);
	assert(wrapped.native_value() == static_cast<std::int8_t>(130));

	scpp::int_t<std::int16_t> wider_signed(2);
	auto signed_sum = small + wider_signed;
	static_assert(std::is_same_v<decltype(signed_sum), scpp::int_t<std::int16_t>>);
	assert(signed_sum.native_value() == 122);

	scpp::int_t<std::uint8_t> u8(250);
	scpp::int_t<std::uint16_t> u16(10);
	auto unsigned_sum = u8 + u16;
	static_assert(std::is_same_v<decltype(unsigned_sum), scpp::int_t<std::uint16_t>>);
	assert(unsigned_sum.native_value() == 260);

	auto cast_down = scpp::cast<scpp::int_t<std::int8_t>>(scpp::int_t<>(127));
	assert(cast_down.native_value() == 127);
	assert(scpp::cast<scpp::bool_t>(cast_down).native_value() == true);
	assert(scpp::cast<scpp::string_t>(unsigned_sum).native_value() == "260");
	assert(scpp::php::to_string(cast_down).native_value() == "127");
	assert(scpp::php::condition_truthy(cast_down).native_value() == true);
	assert(scpp::php::is_int(cast_down).native_value() == true);
	assert(scpp::php::empty(scpp::int_t<std::uint8_t>(0)).native_value() == true);

	return 0;
}
