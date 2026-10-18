#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/vector_t.hpp"
#include "operators/count/count.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::unique_p<scpp::int_t<>>> local_v;
	return static_cast<int>((scpp::count(static_cast<const scpp::vector_t<scpp::unique_p<scpp::int_t<>>>&>(local_v))).native_value());
	return 0;
}
