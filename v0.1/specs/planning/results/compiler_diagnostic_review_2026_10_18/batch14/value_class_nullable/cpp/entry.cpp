#include "types/n43.hpp"
#include "scpp/value_p.hpp"
#include "scpp/nullable.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::value_p<record_C> local_c = scpp::value_p<record_C>{};
	scpp::nullable<scpp::value_p<record_C>> local_n = scpp::nullable<scpp::value_p<record_C>>{local_c};
	scpp::value_p<record_C> local_d = scpp::value_p<record_C>((local_n).require_value("nullable required-value extraction"));
	return static_cast<int>(((local_d)->field_n).native_value());
	return 0;
}
