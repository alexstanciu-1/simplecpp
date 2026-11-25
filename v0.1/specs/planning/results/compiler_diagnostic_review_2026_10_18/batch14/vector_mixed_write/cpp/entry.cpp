#include "scpp/data_initializer.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/string_t.hpp"
#include "scpp/index_read.hpp"
#include "scpp/cast.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::mixed_t> local_v = ([&]() -> scpp::vector_t<scpp::mixed_t> {
scpp::initializer_buffer<scpp::mixed_t> initializer_0(1);
{
scpp::mixed_t initializer_value_1 = scpp::mixed_t{static_cast<scpp::int_t<>>(1LL)};
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<scpp::mixed_t>>::from_list(initializer_0.view());
}());
	{ auto& write_receiver_2 = local_v; scpp::int_t<> write_key_2 = static_cast<scpp::int_t<>>(0LL); scpp::require_write_key(write_key_2); scpp::mixed_t write_value_2 = scpp::mixed_t{scpp::string_t("x")}; scpp::index_store(write_receiver_2, write_key_2, write_value_2); }
	scpp::string_t local_s = scpp::required_cast<scpp::string_t>(([&]() -> scpp::mixed_t { const auto& index_receiver_3 = local_v; scpp::int_t<> index_key_3 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_3, index_key_3); }()));
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
