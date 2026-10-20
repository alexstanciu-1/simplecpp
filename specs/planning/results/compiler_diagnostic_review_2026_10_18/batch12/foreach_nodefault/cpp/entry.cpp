#include "scpp/data_initializer.hpp"
#include "types/n53.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/value_iteration.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<record_S> local_v = ([&]() -> scpp::vector_t<record_S> {
scpp::initializer_buffer<record_S> initializer_0(1);
{
record_S initializer_value_1 = ([&]() -> record_S {
	scpp::int_t<> argument_2 = static_cast<scpp::int_t<>>(1LL);
	return record_S(argument_2);
}());
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<record_S>>::from_list(initializer_0.view());
}());
{
const auto& foreach_source_3 = local_v;
for (auto foreach_entry_3 : scpp::value_iteration(foreach_source_3)) {
record_S local_s = foreach_entry_3.value_copy();
{
}
}
}
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
