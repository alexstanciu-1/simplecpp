#include "scpp/runtime.hpp"
#include "modules/strings/strings.hpp"

#include <cassert>
#include <cstdint>
#include <limits>

int main() {
	const scpp::string_t separator(",");
	const scpp::string_t input("a,b,c");
	for (const auto limit : {std::numeric_limits<std::int64_t>::min(), std::int64_t(-4), std::int64_t(-3)}) {
		assert(scpp::str::split(separator, input, scpp::int_t<>(limit)).size() == 0);
	}
	const auto kept = scpp::str::split(separator, input, scpp::int_t<>(-2));
	assert(kept.size() == 1);
	assert(kept.native_value()[0].native_value() == "a");
	assert(scpp::str::split(separator, input, scpp::int_t<>(0)).native_value()[0].native_value() == "a,b,c");
	return 0;
}
