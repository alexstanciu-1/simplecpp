#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/index_write.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::hash_t<scpp::int_t<>, scpp::int_t<>> local_h = scpp::data_initializer<scpp::hash_t<scpp::int_t<>, scpp::int_t<>>>::from_list(scpp::initializer_list<scpp::int_t<>>{});
	{ auto& write_receiver_0 = local_h; scpp::int_t<> write_value_0 = static_cast<scpp::int_t<>>(1LL); scpp::index_append(write_receiver_0, write_value_0); }
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
