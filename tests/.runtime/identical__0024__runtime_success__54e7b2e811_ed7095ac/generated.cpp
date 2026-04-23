#include "identical__0024__runtime_success__54e7b2e811.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	mixed_t lhs = static_cast<bool_t>(false);
	mixed_t rhs = string_t("");
	php::var_dump(php::identical(lhs, rhs));
	return 0;
}
}

int main() {
	try {
		return scpp::__scpp_main();
	} catch (const std::exception &exception) {
		::scpp::print_runtime_exception(exception);
		return 1;
	}
}

