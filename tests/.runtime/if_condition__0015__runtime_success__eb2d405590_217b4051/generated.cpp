#include "if_condition__0015__runtime_success__eb2d405590.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	mixed_t lhs = null;
	if (static_cast<bool>(php::condition_truthy(lhs))) {
		php::var_dump(static_cast<bool_t>(true));
	}
	else {
		php::var_dump(static_cast<bool_t>(false));
	}
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

