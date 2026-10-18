#include "types/n53.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_s = record_S{};
	auto local_t = local_s;
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto& receiver_0 = local_t;
	return receiver_0.method_f();
}())).native_value());
	return 0;
}
