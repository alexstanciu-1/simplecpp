#include "types/n53.hpp"
#include "scpp/nullable.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/operators/isset/isset.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::nullable<record_S> local_s = scpp::nullable<record_S>{record_S{}};
	auto local_t = ([&]() -> scpp::nullable<record_S> { if ((static_cast<scpp::bool_t>(true)).native_value()) { return local_s; } return local_s; }());
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((scpp::php::isset(local_t)).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
