#include "scpp/int_t.hpp"
#include "utility"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_f = [](scpp::int_t<>& local_n, scpp::int_t<> local_x) -> scpp::int_t<>& {
	return local_n;
};
	auto local_n = static_cast<scpp::int_t<>>(1LL);
	scpp::int_t<>& local_r = ([&]() -> scpp::int_t<>& {
auto callee_0 = local_f;
	scpp::int_t<>& argument_1 = local_n;
scpp::int_t<> default_argument_2 = static_cast<scpp::int_t<>>(1LL);
	return callee_0(argument_1, std::move(default_argument_2));
}());
	return static_cast<int>((local_r).native_value());
	return 0;
}
