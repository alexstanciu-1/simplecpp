#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"

int main()
{
	scpp::int_t<std::uint8_t> local_v = static_cast<scpp::int_t<std::uint8_t>>((static_cast<scpp::int_t<>>(258LL)).native_value());
	scpp::nullable<scpp::int_t<std::uint8_t>> local_x = scpp::nullable<scpp::int_t<std::uint8_t>>{local_v};
	scpp::nullable<scpp::int_t<std::uint8_t>> local_y = local_x;
	scpp::int_t<std::uint8_t> local_z = scpp::int_t<std::uint8_t>((local_y).require_value("nullable required-value extraction"));
	return static_cast<int>((local_z).native_value());
	return 0;
}
