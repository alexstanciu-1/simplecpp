#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/keyed_path.hpp"
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
	([&]() -> void { auto& keyed_root_3 = local_v; auto keyed_resolve_3_0 = [&]() -> const scpp::hash_t<scpp::int_t<>, scpp::string_t>* { return &keyed_root_3; }; if (!scpp::keyed_ready(keyed_resolve_3_0(), true)) { return; } scpp::mixed_t keyed_key_3_0 = local_k; if (!scpp::keyed_lookup(keyed_resolve_3_0(), keyed_key_3_0, true)) { return; } scpp::keyed_erase(keyed_root_3, keyed_key_3_0); }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
