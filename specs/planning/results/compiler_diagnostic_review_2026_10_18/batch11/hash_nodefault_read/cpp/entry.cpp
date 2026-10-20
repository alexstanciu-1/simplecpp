#include "scpp/data_initializer.hpp"
#include "types/n53.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/index_read.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::hash_t<record_S, scpp::string_t> local_v = ([&]() -> scpp::hash_t<record_S, scpp::string_t> {
scpp::initializer_buffer<scpp::initializer_entry<record_S, scpp::string_t>> initializer_0(1);
{
scpp::string_t initializer_key_1 = scpp::string_t("x");
record_S initializer_value_2 = ([&]() -> record_S {
	scpp::int_t<> argument_3 = static_cast<scpp::int_t<>>(1LL);
	return record_S(argument_3);
}());
initializer_0.append(scpp::initializer_entry<record_S, scpp::string_t>{initializer_key_1, initializer_value_2});
}
return scpp::data_initializer<scpp::hash_t<record_S, scpp::string_t>>::from_key_value(initializer_0.view());
}());
	auto local_s = ([&]() -> record_S { const auto& index_receiver_4 = local_v; scpp::string_t index_key_4 = scpp::string_t("x"); return scpp::checked_index_read(index_receiver_4, index_key_4); }());
	return static_cast<int>(((local_s).field_n).native_value());
	return 0;
}
