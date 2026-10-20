#include "scpp/lang/php.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include <cassert>
#include <cstdint>
#include <limits>

template<class F> void invalid(F operation) {
	bool caught = false;
	try { operation(); } catch (const scpp::ValueError&) { caught = true; }
	assert(caught);
}

template<class Rep> void integers() {
	using I = scpp::int_t<Rep>;
	constexpr int width = std::numeric_limits<std::make_unsigned_t<Rep>>::digits;
	for (int count : {0, 1, width - 1}) {
		// Independent expectations without repeating the tested shift expression.
		auto zero = I{0} << I{count};
		auto one = I{1} >> I{count};
		assert(zero.native_value() == 0);
		assert(one.native_value() == (count == 0 ? 1 : 0));
		if constexpr (std::is_signed_v<Rep>) {
			assert((I{-1} >> I{count}).native_value() == -1);
		}
	}
	assert((I{3} << I{1}).native_value() == 6);
	assert((I{6} >> I{1}).native_value() == 3);
	for (int count : {width, width + 1}) {
		invalid([&] { (void)(I{1} << I{count}); });
		invalid([&] { (void)(I{1} >> I{count}); });
		I target{7};
		invalid([&] { target <<= I{count}; });
		assert(target.native_value() == 7);
		invalid([&] { target >>= I{count}; });
		assert(target.native_value() == 7);
	}
	if constexpr (std::is_signed_v<Rep>) {
		invalid([] { (void)(I{1} << I{-1}); });
		invalid([] { (void)(I{1} >> I{-1}); });
	}
}

template<class Box> void boxed() {
	using I = scpp::int_t<>;
	for (int count : {-1, 64, 65}) {
		invalid([&] { (void)(Box{I{1}} << Box{I{count}}); });
		invalid([&] { (void)(Box{I{1}} >> Box{I{count}}); });
	}
	assert((Box{I{3}} << Box{I{1}}).get_int().native_value() == 6);
	assert((Box{I{6}} >> Box{I{1}}).get_int().native_value() == 3);
	for (int count : {0, 1, 63}) {
		assert((Box{I{0}} << Box{I{count}}).get_int().native_value() == 0);
		assert((Box{I{-1}} >> Box{I{count}}).get_int().native_value() == -1);
	}
	for (auto value : {std::numeric_limits<std::int64_t>::min(), std::numeric_limits<std::int64_t>::max()}) {
		assert((Box{I{value}} << Box{I{0}}).get_int().native_value() == value);
		assert((Box{I{value}} >> Box{I{63}}).get_int().native_value() == (value < 0 ? -1 : 0));
	}
}

template<class Carrier> void lifted() {
	using I = scpp::int_t<>;
	Carrier target{I{7}};
	invalid([&] { target <<= Carrier{I{64}}; });
	assert((target >> I{0}).native_value() == 7);
	invalid([&] { target >>= Carrier{I{-1}}; });
	assert((target << I{0}).native_value() == 7);
}

int main() {
	integers<std::int8_t>(); integers<std::uint8_t>();
	integers<std::int16_t>(); integers<std::uint16_t>();
	integers<std::int32_t>(); integers<std::uint32_t>();
	integers<std::int64_t>(); integers<std::uint64_t>();
	boxed<scpp::mixed_t>(); boxed<scpp::dynamic_t<>>();
	using I = scpp::int_t<>;
	lifted<scpp::nullable<I>>(); lifted<scpp::result<I>>();
	lifted<scpp::result_or_false<I>>(); lifted<scpp::result_or_bool<I>>();
}
