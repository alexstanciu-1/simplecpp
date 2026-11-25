#include "types/n43.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "types/n41.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_c = ::scpp::shared<record_C>();
	::scpp::shared_p<record_A> local_a = ::scpp::shared_p<record_A>{local_c};
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto receiver_0 = local_a;
(void)receiver_0.operator->();
	return receiver_0->method_f();
}())).native_value());
	return 0;
}
