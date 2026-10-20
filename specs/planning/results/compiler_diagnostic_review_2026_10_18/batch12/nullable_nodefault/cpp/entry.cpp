#include "scpp/null_t.hpp"
#include "types/n53.hpp"
#include "scpp/nullable.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::nullable<record_S> local_v = scpp::nullable<record_S>{scpp::null};
	record_S local_s = record_S((local_v).require_value("nullable required-value extraction"));
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
