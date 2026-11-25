#include "types/n43.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_c = ([&]() -> ::scpp::shared_p<record_C> {
	return record_C::implementation_make_for_type_C();
}());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
