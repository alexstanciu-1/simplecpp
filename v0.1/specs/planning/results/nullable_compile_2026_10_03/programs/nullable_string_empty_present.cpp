#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/null_t.hpp"

int main()
{
	scpp::nullable<scpp::string_t> local_x = scpp::nullable<scpp::string_t>{scpp::string_t("")};
	return static_cast<int>(((local_x).has_value()).native_value());
	return 0;
}
