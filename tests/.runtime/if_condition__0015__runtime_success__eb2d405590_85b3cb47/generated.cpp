#include "if_condition__0015__runtime_success__eb2d405590.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	mixed_t lhs = null;
	php::var_dump(php::ternary_eval([&]() -> decltype(auto) { return cast<bool_t>(lhs); }, [&]() -> decltype(auto) { return static_cast<bool_t>(true); }, [&]() -> decltype(auto) { return static_cast<bool_t>(false); }));
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

