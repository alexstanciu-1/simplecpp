#include "scpp/int_t.hpp"
#include "functional"
#include "scpp/data_initializer.hpp"
#include "scpp/result.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/index_read.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	std::function<scpp::int_t<>()> local_f = std::function<scpp::int_t<>()>{[]() -> scpp::int_t<> {
	return static_cast<scpp::int_t<>>(1LL);
}};
	scpp::vector_t<scpp::result<std::function<scpp::int_t<>()>>> local_v = ([&]() -> scpp::vector_t<scpp::result<std::function<scpp::int_t<>()>>> {
scpp::initializer_buffer<scpp::result<std::function<scpp::int_t<>()>>> initializer_0(1);
{
scpp::result<std::function<scpp::int_t<>()>> initializer_value_1 = scpp::result<std::function<scpp::int_t<>()>>{local_f};
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<scpp::result<std::function<scpp::int_t<>()>>>>::from_list(initializer_0.view());
}());
	auto local_r = ([&]() -> scpp::result<std::function<scpp::int_t<>()>> { const auto& index_receiver_2 = local_v; scpp::int_t<> index_key_2 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_2, index_key_2); }());
	std::function<scpp::int_t<>()> local_g = std::function<scpp::int_t<>()>((local_r).require_value("Result required-value extraction"));
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto callee_3 = local_g;
	return callee_3();
}())).native_value());
	return 0;
}
