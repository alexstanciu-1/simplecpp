#include "scpp/data_initializer.hpp"
#include "types/n53.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::hash_t<record_S, scpp::string_t> local_v = scpp::data_initializer<scpp::hash_t<record_S, scpp::string_t>>::from_key_value(scpp::initializer_list<scpp::initializer_entry<record_S, scpp::string_t>>{});
	{ auto& write_receiver_0 = local_v; scpp::string_t write_key_0 = scpp::string_t("x"); scpp::require_write_key(write_key_0); record_S write_value_0 = ([&]() -> record_S {
	scpp::int_t<> argument_1 = static_cast<scpp::int_t<>>(1LL);
	return record_S(argument_1);
}()); scpp::index_store(write_receiver_0, write_key_0, write_value_0); }
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
