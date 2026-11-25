#include "scpp/data_initializer.hpp"
#include "types/n53.hpp"
#include "scpp/nullable.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/int_t.hpp"
#include "scpp/index_read.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::nullable<record_S>> local_v = ([&]() -> scpp::vector_t<scpp::nullable<record_S>> {
scpp::initializer_buffer<scpp::nullable<record_S>> initializer_0(2);
{
scpp::nullable<record_S> initializer_value_1 = scpp::nullable<record_S>{record_S{}};
initializer_0.append(initializer_value_1);
}
{
scpp::nullable<record_S> initializer_value_2 = scpp::nullable<record_S>{scpp::null};
initializer_0.append(initializer_value_2);
}
return scpp::data_initializer<scpp::vector_t<scpp::nullable<record_S>>>::from_list(initializer_0.view());
}());
	{ auto& write_receiver_3 = local_v; scpp::int_t<> write_key_3 = static_cast<scpp::int_t<>>(1LL); scpp::require_write_key(write_key_3); scpp::nullable<record_S> write_value_3 = scpp::nullable<record_S>{record_S{}}; scpp::index_store(write_receiver_3, write_key_3, write_value_3); }
	record_S local_s = record_S((([&]() -> scpp::nullable<record_S> { const auto& index_receiver_4 = local_v; scpp::int_t<> index_key_4 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_4, index_key_4); }())).require_value("nullable required-value extraction"));
	return static_cast<int>(((local_s).field_n).native_value());
	return 0;
}
