#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/vector_t.hpp"
#include "lang/php/operators/empty/empty.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::unique_p<scpp::int_t<>>> local_v;
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((scpp::php::empty(local_v)).native_value()) { return static_cast<scpp::int_t<>>(0LL); } return static_cast<scpp::int_t<>>(1LL); }())).native_value());
	return 0;
}
