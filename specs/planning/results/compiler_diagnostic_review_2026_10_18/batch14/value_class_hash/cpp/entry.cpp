#include "types/n43.hpp"
#include "scpp/value_p.hpp"
#include "scpp/data_initializer.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/index_read.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::value_p<record_C> local_c = scpp::value_p<record_C>{};
	scpp::hash_t<scpp::value_p<record_C>, scpp::string_t> local_h = ([&]() -> scpp::hash_t<scpp::value_p<record_C>, scpp::string_t> {
scpp::initializer_buffer<scpp::initializer_entry<scpp::value_p<record_C>, scpp::string_t>> initializer_0(1);
{
scpp::string_t initializer_key_1 = scpp::string_t("x");
scpp::value_p<record_C> initializer_value_2 = local_c;
initializer_0.append(scpp::initializer_entry<scpp::value_p<record_C>, scpp::string_t>{initializer_key_1, initializer_value_2});
}
return scpp::data_initializer<scpp::hash_t<scpp::value_p<record_C>, scpp::string_t>>::from_key_value(initializer_0.view());
}());
	{ auto& write_receiver_3 = local_h; scpp::string_t write_key_3 = scpp::string_t("x"); scpp::require_write_key(write_key_3); scpp::value_p<record_C> write_value_3 = local_c; scpp::index_store(write_receiver_3, write_key_3, write_value_3); }
	auto local_d = ([&]() -> scpp::value_p<record_C> { const auto& index_receiver_4 = local_h; scpp::string_t index_key_4 = scpp::string_t("x"); return scpp::checked_index_read(index_receiver_4, index_key_4); }());
	return static_cast<int>(((local_d)->field_n).native_value());
	return 0;
}
