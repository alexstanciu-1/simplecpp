#include "add_assign__0110__runtime_success__c2b40cdfc4.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	mixed_t lhs = static_cast<int_t>(0);
	mixed_t rhs = static_cast<float_t>(0);
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

