#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "scpp/int_t.hpp"

int main()
{
	scpp::nullable<scpp::string_t> local_x = scpp::nullable<scpp::string_t>{scpp::string_t("7")};
	scpp::nullable<scpp::string_t> local_y = local_x;
	local_x = scpp::nullable<scpp::string_t>{scpp::string_t("8")};
	scpp::string_t local_z = scpp::string_t((local_y).require_value("nullable required-value extraction"));
	return static_cast<int>((scpp::cast<scpp::int_t<>>(local_z)).native_value());
	return 0;
}
