#include "types/n43.hpp"
#include "scpp/value_p.hpp"
#include "scpp/data_initializer.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/int_t.hpp"
#include "scpp/index_read.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::value_p<record_C> local_c = scpp::value_p<record_C>{};
	scpp::vector_t<scpp::value_p<record_C>> local_v = ([&]() -> scpp::vector_t<scpp::value_p<record_C>> {
scpp::initializer_buffer<scpp::value_p<record_C>> initializer_0(1);
{
scpp::value_p<record_C> initializer_value_1 = local_c;
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<scpp::value_p<record_C>>>::from_list(initializer_0.view());
}());
	{ auto& write_receiver_2 = local_v; scpp::int_t<> write_key_2 = static_cast<scpp::int_t<>>(0LL); scpp::require_write_key(write_key_2); scpp::value_p<record_C> write_value_2 = local_c; scpp::index_store(write_receiver_2, write_key_2, write_value_2); }
	auto local_d = ([&]() -> scpp::value_p<record_C> { const auto& index_receiver_3 = local_v; scpp::int_t<> index_key_3 = static_cast<scpp::int_t<>>(0LL); return scpp::checked_index_read(index_receiver_3, index_key_3); }());
	return static_cast<int>(((local_d)->field_n).native_value());
	return 0;
}
