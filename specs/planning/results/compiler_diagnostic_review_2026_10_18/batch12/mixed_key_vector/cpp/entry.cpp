#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/index_read.hpp"
#include "scpp/cast.hpp"
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
	scpp::mixed_t local_k = scpp::mixed_t{static_cast<scpp::int_t<>>(0LL)};
	auto local_x = ([&]() -> scpp::int_t<> { const auto& index_receiver_2 = local_v; scpp::int_t<> index_key_2 = scpp::required_cast<scpp::int_t<>>(local_k); return scpp::checked_index_read(index_receiver_2, index_key_2); }());
	return static_cast<int>((local_x).native_value());
	return 0;
}
