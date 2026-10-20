#include "scpp/data_initializer.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/index_existing.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::hash_t<scpp::string_t, scpp::string_t> local_v = ([&]() -> scpp::hash_t<scpp::string_t, scpp::string_t> {
scpp::initializer_buffer<scpp::initializer_entry<scpp::string_t, scpp::string_t>> initializer_0(1);
{
scpp::string_t initializer_key_1 = scpp::string_t("x");
scpp::string_t initializer_value_2 = scpp::string_t("a");
initializer_0.append(scpp::initializer_entry<scpp::string_t, scpp::string_t>{initializer_key_1, initializer_value_2});
}
return scpp::data_initializer<scpp::hash_t<scpp::string_t, scpp::string_t>>::from_key_value(initializer_0.view());
}());
	([&]() -> scpp::string_t { auto& update_receiver_3 = local_v; auto update_key_3_0 = scpp::string_t("x"); scpp::require_write_key(update_key_3_0); auto update_previous_3 = scpp::index_existing_read(update_receiver_3, update_key_3_0); update_previous_3 = ([&]() -> scpp::string_t { auto operand_4 = update_previous_3; auto operand_5 = scpp::string_t("b"); return (operand_4 + operand_5); }()); scpp::index_existing_store(update_receiver_3, update_previous_3, update_key_3_0); return update_previous_3; }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
