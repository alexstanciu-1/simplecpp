#include "scpp/integers.hpp"
#include "scpp/detail/concrete_integer_keys.hpp"
#include "scpp/generated/operators.hpp"
#include <limits>
#include <type_traits>
#include <string>

// Compare the public facade with the semantic authority, including failure metadata.
template<class Public, class Rep>
bool check_integer()
{
	using Native = scpp::int_t<Rep>;
	static_assert(sizeof(Public) == sizeof(Rep));
	static_assert(alignof(Public) == alignof(Rep));
	static_assert(std::is_trivially_copyable_v<Public>);
	static_assert(!std::is_convertible_v<Public, Rep>);
	if (Public().native_value() != 0) { return false; }
	for (Rep a : {Rep(0), Rep(1), Rep(7), Rep(23)}) {
		for (Rep b : {Rep(1), Rep(2), Rep(7)}) {
			const Public x(a), y(b);
			const Native nx(a), ny(b);
			if ((x + y).native_value() != (nx + ny).native_value() ||
				(x - y).native_value() != (nx - ny).native_value() ||
				(x * y).native_value() != (nx * ny).native_value() ||
				(x / y).native_value() != (nx / ny).native_value() ||
				(x % y).native_value() != (nx % ny).native_value() ||
				(x == y).native_value() != (nx == ny).native_value() ||
				(x != y).native_value() != (nx != ny).native_value() ||
				(x < y).native_value() != (nx < ny).native_value() ||
				(x <= y).native_value() != (nx <= ny).native_value() ||
				(x > y).native_value() != (nx > ny).native_value() ||
				(x >= y).native_value() != (nx >= ny).native_value()) { return false; }
		}
	}
	Public value(7);
	value += Public(3); value -= Public(2); value *= Public(4);
	value /= Public(2); value %= Public(7); ++value; --value;
	if (value.native_value() != 2) { return false; }
	for (bool modulo : {false, true}) {
		std::string expected;
		try { (void)(modulo ? Native(9) % Native(0) : Native(9) / Native(0)); }
		catch (const scpp::runtime_error &error) {
			expected = error.code() + error.component() + error.operator_symbol() + error.what();
		}
		if (expected.empty()) { return false; }
		value = Public(9);
		try { if (modulo) { value %= Public(0); } else { value /= Public(0); } return false; }
		catch (const scpp::runtime_error &error) {
			if (expected != error.code() + error.component() + error.operator_symbol() + error.what()) { return false; }
		}
		if (value.native_value() != 9) { return false; }
	}
	const Public maximum(std::numeric_limits<Rep>::max());
	if (maximum.native_value() != std::numeric_limits<Rep>::max()) { return false; }
	if constexpr (std::is_unsigned_v<Rep>) {
		value = maximum; ++value;
		if (value.native_value() != 0) { return false; }
		--value;
		if (value.native_value() != maximum.native_value()) { return false; }
	} else {
		if ((Public(-7) / Public(2)).native_value() != -3 ||
			(Public(-7) % Public(2)).native_value() != -1 ||
			Public(std::numeric_limits<Rep>::min()).native_value() != std::numeric_limits<Rep>::min()) { return false; }
	}
	return static_cast<bool>(scpp::isset(Public(0)));
}

template<class Public, class Rep>
bool check_keys()
{
	using Native = scpp::int_t<Rep>;
	scpp::hash_t<scpp::int64, Public> values;
	const Rep inputs[] = {std::numeric_limits<Rep>::min(), Rep(0), Rep(7), std::numeric_limits<Rep>::max()};
	for (Rep value : inputs) {
		Public key(value);
		if (scpp::hash_detail::key_ops<Public>::hash(key) != scpp::hash_detail::key_ops<Native>::hash(Native(value))) return false;
		values.set(key, scpp::int64(42));
		if (!values.has(key).native_value() || values.at(key).native_value() != 42) return false;
	}
	auto copy = values;
	copy.set(Public(7), scpp::int64(9));
	return values.at(Public(7)).native_value() == 42 && copy.at(Public(7)).native_value() == 9;
}

int main()
{
	if (!check_integer<scpp::int8, __INT8_TYPE__>() ||
		!check_integer<scpp::int16, __INT16_TYPE__>() ||
		!check_integer<scpp::uint8, __UINT8_TYPE__>() ||
		!check_integer<scpp::uint16, __UINT16_TYPE__>() ||
		!check_integer<scpp::int32, __INT32_TYPE__>() ||
		!check_integer<scpp::int64, __INT64_TYPE__>() ||
		!check_integer<scpp::uint32, __UINT32_TYPE__>() ||
		!check_integer<scpp::uint64, __UINT64_TYPE__>()) { return 1; }
	if (scpp::int64(scpp::int32(-7)).native_value() != -7 ||
		scpp::uint64(scpp::uint32(42)).native_value() != 42 ||
		scpp::int32(scpp::boolean(true)).native_value() != 1 ||
		scpp::uint32(scpp::uint64(0x100000001ULL)).native_value() != 1 ||
		scpp::uint64(scpp::int64(-1)).native_value() != __UINT64_MAX__) { return 2; }
	if (scpp::int8(scpp::int64(-129)).native_value() != scpp::int_t<__INT8_TYPE__>(-129).native_value() ||
		scpp::uint8(scpp::uint64(256)).native_value() != 0 ||
		scpp::int64(scpp::int8(-128)).native_value() != -128 ||
		scpp::uint16(scpp::uint64(65536)).native_value() != 0 ||
		scpp::int16(scpp::int64(-32769)).native_value() != scpp::int_t<__INT16_TYPE__>(-32769).native_value()) return 5;
	if (!check_keys<scpp::int8, __INT8_TYPE__>()) return 6;
	if (!check_keys<scpp::int16, __INT16_TYPE__>()) return 6;
	if (!check_keys<scpp::int32, __INT32_TYPE__>()) return 6;
	if (!check_keys<scpp::int64, __INT64_TYPE__>()) return 6;
	if (!check_keys<scpp::uint8, __UINT8_TYPE__>()) return 6;
	if (!check_keys<scpp::uint16, __UINT16_TYPE__>()) return 6;
	if (!check_keys<scpp::uint32, __UINT32_TYPE__>()) return 6;
	if (!check_keys<scpp::uint64, __UINT64_TYPE__>()) return 6;
	scpp::boolean reached = false;
	if (scpp::boolean(false) && (reached = true)) { return 3; }
	if (reached || !scpp::isset(scpp::boolean(false))) { return 4; }
	return 0;
}
