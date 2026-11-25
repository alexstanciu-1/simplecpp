#include "operators/arithmetic/power.hpp"

#include <cassert>
#include <cstdint>
#include <limits>
#include <string_view>
#include <type_traits>

template <typename Base, typename Exponent>
concept has_power = requires(Base base, Exponent exponent) { scpp::pow(base, exponent); };

static_assert(has_power<scpp::int_t<>, scpp::int_t<>>);
static_assert(!has_power<scpp::int_t<std::uint8_t>, scpp::int_t<>>);
static_assert(!has_power<scpp::int_t<>, double>);
static_assert(std::is_same_v<decltype(scpp::pow(scpp::int_t<>(2), scpp::int_t<>(3))), scpp::int_t<>>);

// Check machine-readable diagnostics as well as exception delivery.
void expect_error(std::int64_t base, std::int64_t exponent, std::string_view code) {
	try {
		(void)scpp::pow(scpp::int_t<>(base), scpp::int_t<>(exponent));
	}
	catch (const scpp::runtime_error &error) {
		assert(error.code() == code);
		assert(error.component() == "scpp::pow");
		assert(error.operator_symbol() == "**");
		return;
	}
	assert(false && "power unexpectedly succeeded");
}

int main() {
	constexpr auto maximum = std::numeric_limits<std::int64_t>::max();
	constexpr auto minimum = std::numeric_limits<std::int64_t>::min();
	const auto power = [](std::int64_t base, std::int64_t exponent) {
		return scpp::pow(scpp::int_t<>(base), scpp::int_t<>(exponent)).native_value();
	};
	assert(power(0, 0) == 1);
	assert(power(minimum, 0) == 1);
	assert(power(maximum, 1) == maximum);
	assert(power(minimum, 1) == minimum);
	assert(power(-2, 63) == minimum);
	assert(power(2, 62) == 4611686018427387904LL);
	assert(power(-3, 39) == -4052555153018976267LL);
	assert(power(3037000499LL, 2) == 9223372030926249001LL);
	assert(power(0, maximum) == 0);
	assert(power(1, maximum) == 1);
	assert(power(-1, maximum) == -1);
	assert(power(-1, maximum - 1) == 1);
	for (auto base : {minimum, std::int64_t{-2}, std::int64_t{-1}, std::int64_t{0}, std::int64_t{1}, maximum}) {
		expect_error(base, -1, "power_negative_exponent");
		expect_error(base, minimum, "power_negative_exponent");
	}
	expect_error(2, 63, "power_overflow");
	expect_error(-2, 64, "power_overflow");
	expect_error(maximum, 2, "power_overflow");
	expect_error(minimum, 2, "power_overflow");
	expect_error(minimum, 3, "power_overflow");
	expect_error(3037000500LL, 2, "power_overflow");
	expect_error(2, maximum, "power_overflow");
	// Independent repeated multiplication in a wider test-only type, not squaring.
	for (std::int64_t base = -10; base <= 10; ++base) {
		__int128 expected = 1;
		for (std::int64_t exponent = 0; exponent <= 20; ++exponent) {
			if (expected < minimum || expected > maximum) {
				expect_error(base, exponent, "power_overflow");
			}
			else {
				assert(power(base, exponent) == expected);
			}
			expected *= base;
		}
	}
}
