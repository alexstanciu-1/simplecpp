#include "scpp/data_initializer.hpp"
#include "types/n42.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/vector_t.hpp"
#include "types/n44.hpp"
#include "scpp/memory.hpp"
#include "scpp/index_write.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<::scpp::shared_p<record_B>> local_v = ([&]() -> scpp::vector_t<::scpp::shared_p<record_B>> {
scpp::initializer_buffer<::scpp::shared_p<record_B>> initializer_0(1);
{
::scpp::shared_p<record_B> initializer_value_1 = ::scpp::shared_p<record_B>{::scpp::shared<record_D>()};
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<::scpp::shared_p<record_B>>>::from_list(initializer_0.view());
}());
	{ auto& write_receiver_2 = local_v; ::scpp::shared_p<record_B> write_value_2 = ::scpp::shared_p<record_B>{::scpp::shared<record_D>()}; scpp::index_append(write_receiver_2, write_value_2); }
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
