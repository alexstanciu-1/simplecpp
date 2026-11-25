#include "scpp/int_t.hpp"
#include "scpp/dynamic_t.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "scpp/bool_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::dynamic_t<> local_x = scpp::dynamic_t<>{static_cast<scpp::int_t<>>(1LL)};
	auto local_z = scpp::bool_t((scpp::php::condition_truthy(local_x).native_value()) && (scpp::php::condition_truthy(local_x).native_value()));
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
