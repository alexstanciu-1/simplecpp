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
	mixed_t lhs = static_cast<bool_t>(false);
	mixed_t rhs = static_cast<float_t>(0);
	php::var_dump((lhs == rhs));
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

