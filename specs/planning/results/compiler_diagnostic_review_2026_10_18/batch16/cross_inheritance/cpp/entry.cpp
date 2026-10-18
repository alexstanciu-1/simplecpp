#include "types/n445c4368696c64.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "types/n425c42617365.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_c = ::scpp::shared<record_DUqChild>();
	::scpp::shared_p<record_BUqBase> local_b = ::scpp::shared_p<record_BUqBase>{local_c};
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto receiver_0 = local_b;
(void)receiver_0.operator->();
	return receiver_0->method_f();
}())).native_value());
	return 0;
}
