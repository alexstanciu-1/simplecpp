#include "scpp/int_t.hpp"
#include "functional"
#include "scpp/result.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	std::function<scpp::int_t<>()> local_f = std::function<scpp::int_t<>()>{[]() -> scpp::int_t<> {
	return static_cast<scpp::int_t<>>(1LL);
}};
	scpp::result<std::function<scpp::int_t<>()>> local_r = scpp::result<std::function<scpp::int_t<>()>>{local_f};
	auto local_g = ([&]() -> std::function<scpp::int_t<>()> { scpp::result<std::function<scpp::int_t<>()>> coalesce_0 = local_r; if (coalesce_0.has_value().native_value()) { return std::function<scpp::int_t<>()>((coalesce_0).require_value("Result required-value extraction")); } return local_f; }());
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto callee_1 = local_g;
	return callee_1();
}())).native_value());
	return 0;
}
