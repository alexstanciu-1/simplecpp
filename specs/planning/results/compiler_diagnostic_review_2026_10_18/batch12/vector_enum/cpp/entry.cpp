#include "scpp/data_initializer.hpp"
#include "types/n45.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<enum_E> local_v = ([&]() -> scpp::vector_t<enum_E> {
scpp::initializer_buffer<enum_E> initializer_0(1);
{
enum_E initializer_value_1 = enum_E::case_A;
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<enum_E>>::from_list(initializer_0.view());
}());
	{ auto& write_receiver_2 = local_v; scpp::int_t<> write_key_2 = static_cast<scpp::int_t<>>(0LL); scpp::require_write_key(write_key_2); enum_E write_value_2 = enum_E::case_A; scpp::index_store(write_receiver_2, write_key_2, write_value_2); }
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
