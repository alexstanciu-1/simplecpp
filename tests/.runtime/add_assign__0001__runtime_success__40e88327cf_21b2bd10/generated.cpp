#include "add_assign__0001__runtime_success__40e88327cf.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	float_t lhs = static_cast<float_t>(3.5);
	float_t rhs = static_cast<float_t>(3.5);
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

