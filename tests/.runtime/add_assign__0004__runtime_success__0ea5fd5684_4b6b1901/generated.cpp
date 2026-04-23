#include "add_assign__0004__runtime_success__0ea5fd5684.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	float_t lhs = static_cast<float_t>(0);
	float_t rhs = static_cast<float_t>(0);
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

