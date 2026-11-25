#include "types/n53.hpp"
#include "scpp/memory.hpp"
#include "scpp/unique_p.hpp"
#include "utility"
#include "scpp/int_t.hpp"
#include "lang/php/operators/isset/isset.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::unique_p<record_S> local_a = ::scpp::unique<record_S>();
	scpp::unique_p<record_S>& local_r = local_a;
	auto local_b = scpp::unique_p<record_S>(std::move(local_a));
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((scpp::php::isset(local_r)).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
