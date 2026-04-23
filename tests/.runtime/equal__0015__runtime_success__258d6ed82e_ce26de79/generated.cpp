#include "equal__0015__runtime_success__258d6ed82e.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	mixed_t lhs = static_cast<bool_t>(false);
	mixed_t rhs = static_cast<float_t>(3.5);
	php::var_dump((lhs == rhs));
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

