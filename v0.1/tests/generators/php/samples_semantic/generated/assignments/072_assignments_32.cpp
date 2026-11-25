#include "072_assignments_32.hpp"

namespace scpp {
	using namespace ::scpp::php;

int __scpp_main() {
	auto a = static_cast<int_t>(4);
	auto b = a;
	b = b + static_cast<int_t>(1);
	::scpp::php::echo(b);
	return 0;
}
}

int main() {
	return scpp::__scpp_main();
}

