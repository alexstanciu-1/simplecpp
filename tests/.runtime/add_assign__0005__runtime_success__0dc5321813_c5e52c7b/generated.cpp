#include "add_assign__0005__runtime_success__0dc5321813.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	int_t lhs = static_cast<int_t>(7);
	int_t rhs = static_cast<int_t>(7);
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

