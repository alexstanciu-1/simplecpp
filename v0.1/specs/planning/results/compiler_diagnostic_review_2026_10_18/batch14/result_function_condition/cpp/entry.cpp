#include "scpp/int_t.hpp"
#include "functional"
#include "scpp/result.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	std::function<scpp::int_t<>()> local_f = std::function<scpp::int_t<>()>{[]() -> scpp::int_t<> {
	return static_cast<scpp::int_t<>>(1LL);
}};
	scpp::result<std::function<scpp::int_t<>()>> local_r = scpp::result<std::function<scpp::int_t<>()>>{local_f};
	return static_cast<int>((([&]() -> scpp::int_t<> { if (scpp::php::condition_truthy(local_r).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
