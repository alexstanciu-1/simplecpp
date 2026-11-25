#include "scpp/data_initializer.hpp"
#include "types/n53.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/int_t.hpp"
#include "scpp/index_write_path.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::hash_t<scpp::hash_t<record_S, scpp::string_t>, scpp::string_t> local_v = scpp::data_initializer<scpp::hash_t<scpp::hash_t<record_S, scpp::string_t>, scpp::string_t>>::from_key_value(scpp::initializer_list<scpp::initializer_entry<scpp::hash_t<record_S, scpp::string_t>, scpp::string_t>>{});
	{ auto& write_receiver_0 = local_v; scpp::string_t write_path_key_0_0 = scpp::string_t("x"); scpp::require_write_key(write_path_key_0_0); scpp::string_t write_key_0 = scpp::string_t("y"); scpp::require_write_key(write_key_0); record_S write_value_0 = ([&]() -> record_S {
	scpp::int_t<> argument_1 = static_cast<scpp::int_t<>>(1LL);
	return record_S(argument_1);
}()); { const scpp::hash_t<record_S, scpp::string_t>& write_probe_0_0 = scpp::index_path_probe(write_receiver_0, write_path_key_0_0); scpp::index_path_check_store(write_probe_0_0, write_key_0, write_value_0); } scpp::hash_t<record_S, scpp::string_t>& write_target_0_0 = scpp::index_path_descend(write_receiver_0, write_path_key_0_0); scpp::index_store(write_target_0_0, write_key_0, write_value_0); }
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
