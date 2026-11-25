#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/index_read.hpp"
#include "scpp/cast.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::hash_t<scpp::int_t<>, scpp::string_t> local_v = ([&]() -> scpp::hash_t<scpp::int_t<>, scpp::string_t> {
scpp::initializer_buffer<scpp::initializer_entry<scpp::int_t<>, scpp::string_t>> initializer_0(1);
{
scpp::string_t initializer_key_1 = scpp::string_t("x");
scpp::int_t<> initializer_value_2 = static_cast<scpp::int_t<>>(1LL);
initializer_0.append(scpp::initializer_entry<scpp::int_t<>, scpp::string_t>{initializer_key_1, initializer_value_2});
}
return scpp::data_initializer<scpp::hash_t<scpp::int_t<>, scpp::string_t>>::from_key_value(initializer_0.view());
}());
	scpp::mixed_t local_k = scpp::mixed_t{scpp::string_t("x")};
	return static_cast<int>((([&]() -> scpp::int_t<> { const auto& index_receiver_3 = local_v; scpp::string_t index_key_3 = scpp::required_cast<scpp::string_t>(local_k); return scpp::checked_index_read(index_receiver_3, index_key_3); }())).native_value());
	return 0;
}
