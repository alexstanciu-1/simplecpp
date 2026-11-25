#include "types/n53.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_s = record_S{};
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((static_cast<void>(local_s), true)) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
