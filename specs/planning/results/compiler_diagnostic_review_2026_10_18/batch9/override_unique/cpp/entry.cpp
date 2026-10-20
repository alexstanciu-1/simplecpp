#include "types/n43.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "types/n49.hpp"
#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "utility"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_c = ::scpp::shared<record_C>();
	::scpp::shared_p<record_I> local_i = ::scpp::shared_p<record_I>{local_c};
	scpp::unique_p<scpp::int_t<>> local_p;
	auto local_q = ([&]() -> scpp::unique_p<scpp::int_t<>> {
auto receiver_0 = local_i;
(void)receiver_0.operator->();
	scpp::unique_p<scpp::int_t<>> argument_1 = scpp::unique_p<scpp::int_t<>>(std::move(local_p));
	return receiver_0->method_f(std::move(argument_1));
}());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
