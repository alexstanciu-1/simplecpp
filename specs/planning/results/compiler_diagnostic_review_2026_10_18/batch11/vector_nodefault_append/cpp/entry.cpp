#include "scpp/data_initializer.hpp"
#include "types/n53.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/index_write.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<record_S> local_v = scpp::data_initializer<scpp::vector_t<record_S>>::from_list(scpp::initializer_list<record_S>{});
	{ auto& write_receiver_0 = local_v; record_S write_value_0 = ([&]() -> record_S {
	scpp::int_t<> argument_1 = static_cast<scpp::int_t<>>(1LL);
	return record_S(argument_1);
}()); scpp::index_append(write_receiver_0, write_value_0); }
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
