#include "casts_005_condition_bool_cast_path.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	auto n = static_cast<int_t>(1);
	if (static_cast<bool>(php::condition_truthy(n))) {
		php::echo_eval([&]() -> decltype(auto) { return string_t("yes\n"); });
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

