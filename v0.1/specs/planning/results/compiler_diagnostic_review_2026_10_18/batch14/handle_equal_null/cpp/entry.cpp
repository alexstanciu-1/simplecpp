#include "types/n43.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	::scpp::shared_p<record_C> local_c;
	return static_cast<int>((([&]() -> scpp::int_t<> { if (((!(local_c).has_value())).native_value()) { return static_cast<scpp::int_t<>>(0LL); } return static_cast<scpp::int_t<>>(1LL); }())).native_value());
	return 0;
}
