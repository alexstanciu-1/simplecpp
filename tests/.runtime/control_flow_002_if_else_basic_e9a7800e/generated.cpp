#include "control_flow_002_if_else_basic.hpp"

namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	auto value = static_cast<int_t>(2);
	if (static_cast<bool>(php::identical(value, static_cast<int_t>(1)))) {
		php::echo_eval([&]() -> decltype(auto) { return string_t("one\n"); });
	}
	else {
		php::echo_eval([&]() -> decltype(auto) { return string_t("other\n"); });
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

