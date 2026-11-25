#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/index_read.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::int_t<>> local_v = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_0(1);
{
scpp::int_t<> initializer_value_1 = static_cast<scpp::int_t<>>(1LL);
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_0.view());
}());
	auto local_r = ([&]() -> scpp::int_t<> { const auto& index_receiver_2 = local_v; scpp::int_t<> index_key_2 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_2, index_key_2); }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
