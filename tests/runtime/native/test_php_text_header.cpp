// This header must supply its own cast dependencies, without the umbrella/PCH.
#include "lang/php/support/php_string.hpp"
#include "scpp/memory.hpp"
#include <cassert>

int main() {
	auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	scpp::mixed_t owned{scpp::unique<scpp::hash_t<scpp::mixed_t>>()};
	scpp::mixed_t shared{table};
	scpp::mixed_t weak{scpp::weak(table)};
	scpp::mixed_t object{scpp::dynamic_box(table)};
	assert(scpp::php::to_string(owned).native_value() == "Array");
	assert(scpp::php::to_string(shared).native_value() == "Array");
	assert(scpp::php::to_string(weak).native_value() == "Array");
	assert(scpp::php::to_string(object).native_value() == "Object");
	assert(scpp::php::to_string(scpp::mixed_t{false}).native_value() == "");
	return 0;
}
