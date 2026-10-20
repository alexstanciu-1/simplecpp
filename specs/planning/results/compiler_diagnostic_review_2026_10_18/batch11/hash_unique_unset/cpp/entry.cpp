#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/keyed_path.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::hash_t<scpp::unique_p<scpp::int_t<>>, scpp::string_t> local_v;
	([&]() -> void { auto& keyed_root_0 = local_v; auto keyed_resolve_0_0 = [&]() -> const scpp::hash_t<scpp::unique_p<scpp::int_t<>>, scpp::string_t>* { return &keyed_root_0; }; if (!scpp::keyed_ready(keyed_resolve_0_0(), true)) { return; } scpp::string_t keyed_key_0_0 = scpp::string_t("x"); if (!scpp::keyed_lookup(keyed_resolve_0_0(), keyed_key_0_0, true)) { return; } scpp::keyed_erase(keyed_root_0, keyed_key_0_0); }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
