#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/index_existing.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::vector_t<scpp::int_t<>>> local_v = ([&]() -> scpp::vector_t<scpp::vector_t<scpp::int_t<>>> {
scpp::initializer_buffer<scpp::vector_t<scpp::int_t<>>> initializer_0(1);
{
scpp::vector_t<scpp::int_t<>> initializer_value_1 = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_2(1);
{
scpp::int_t<> initializer_value_3 = static_cast<scpp::int_t<>>(1LL);
initializer_2.append(initializer_value_3);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_2.view());
}());
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<scpp::vector_t<scpp::int_t<>>>>::from_list(initializer_0.view());
}());
	([&]() -> scpp::int_t<> { auto& update_receiver_4 = local_v; auto update_key_4_0 = static_cast<scpp::int_t<>>(0LL); scpp::require_write_key(update_key_4_0); auto update_key_4_1 = static_cast<scpp::int_t<>>(0LL); scpp::require_write_key(update_key_4_1); auto update_previous_4 = scpp::index_existing_read(update_receiver_4, update_key_4_0, update_key_4_1); update_previous_4 = ([&]() -> scpp::int_t<> { auto operand_5 = update_previous_4; auto operand_6 = static_cast<scpp::int_t<>>(2LL); return (operand_5 + operand_6); }()); scpp::index_existing_store(update_receiver_4, update_previous_4, update_key_4_0, update_key_4_1); return update_previous_4; }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
