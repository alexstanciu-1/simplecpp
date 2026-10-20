#include "lang/php/operators/empty/empty.hpp"
#include "scpp/string_t.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/bool_t.hpp"
#include "lang/php/operators/isset/isset.hpp"

int main()
{
	return static_cast<int>((scpp::bool_t(static_cast<bool>(scpp::bool_t(static_cast<bool>(scpp::php::empty(scpp::string_t(""))) && static_cast<bool>((!scpp::php::empty(scpp::string_t("0")))))) && static_cast<bool>(scpp::php::isset(scpp::string_t(""))))).native_value());
	return 0;
}
