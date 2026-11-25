#include "126_sentinels_26.hpp"

namespace scpp {
	using namespace ::scpp::php;

int __scpp_main() {
	// ERROR: untyped null assignment rejected
	if (((a == null)).native_value()) {
		::scpp::php::echo(string_t("null"));
	}
	else {
		::scpp::php::echo(string_t("not-null"));
	}
	return 0;
}
}

int main() {
	return scpp::__scpp_main();
}

