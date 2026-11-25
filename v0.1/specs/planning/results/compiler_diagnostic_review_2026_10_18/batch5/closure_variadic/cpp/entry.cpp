#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_n = static_cast<scpp::int_t<>>(1LL);
	auto local_f = [local_n](scpp::vector_t<scpp::int_t<>> local_xs) mutable -> scpp::int_t<> {
	return local_n;
};
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto callee_0 = local_f;
	scpp::int_t<> argument_1 = static_cast<scpp::int_t<>>(1LL);
	scpp::int_t<> argument_2 = static_cast<scpp::int_t<>>(2LL);
	return callee_0(scpp::vector_t<scpp::int_t<>>{argument_1, argument_2});
}())).native_value());
	return 0;
}
