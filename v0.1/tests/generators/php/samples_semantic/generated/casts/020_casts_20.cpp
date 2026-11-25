#include "020_casts_20.hpp"

namespace scpp {
	using namespace ::scpp::php;

int __scpp_main() {
	auto a = static_cast<int_t>(4);
	auto b = cast<string_t>(a);
	::scpp::php::echo(b);
	return 0;
}
}

int main() {
	return scpp::__scpp_main();
}

