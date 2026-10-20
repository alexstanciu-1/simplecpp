#include "types/n415c4974656d.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "types/n425c4974656d.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_a = ::scpp::shared<record_AUqItem>();
	auto local_b = ::scpp::shared<record_BUqItem>();
	(local_a)->field_other = local_b;
	(local_b)->field_other = local_a;
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
