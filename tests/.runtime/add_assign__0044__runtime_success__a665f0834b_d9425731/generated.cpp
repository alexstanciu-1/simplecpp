#include "add_assign__0044__runtime_success__a665f0834b.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	mixed_t lhs = static_cast<float_t>(3.5);
	mixed_t rhs = static_cast<int_t>(0);
	php::var_dump((lhs += rhs));
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

