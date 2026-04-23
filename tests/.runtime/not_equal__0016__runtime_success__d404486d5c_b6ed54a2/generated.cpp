#include "not_equal__0016__runtime_success__d404486d5c.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	mixed_t lhs = static_cast<bool_t>(false);
	mixed_t rhs = static_cast<float_t>(0);
	php::var_dump(php::not_identical(lhs, rhs));
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

