#pragma once

#include <scpp/lang/php.hpp>
#include <type_traits>
#include <utility>

namespace scpp {

int __scpp_main();

}


namespace scpp {
	using namespace ::scpp;

int __scpp_main() {
	auto failed = static_cast<bool_t>(false);
	mixed_t missing = null;
	auto present = static_cast<int_t>(7);
	if (static_cast<bool>(php::identical(failed, static_cast<bool_t>(false)))) {
		php::echo_eval([&]() -> decltype(auto) { return string_t("false\n"); });
	}
	if (static_cast<bool>(php::identical(missing, null))) {
		php::echo_eval([&]() -> decltype(auto) { return string_t("null\n"); });
	}
	if (static_cast<bool>(php::identical(present, static_cast<int_t>(7)))) {
		php::echo_eval([&]() -> decltype(auto) { return string_t("value\n"); });
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

