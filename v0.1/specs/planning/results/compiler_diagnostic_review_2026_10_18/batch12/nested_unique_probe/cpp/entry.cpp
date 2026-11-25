#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/keyed_path.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::hash_t<scpp::unique_p<scpp::int_t<>>, scpp::string_t>> local_h;
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((([&]() -> scpp::bool_t { const auto& keyed_root_0 = local_h; auto keyed_resolve_0_0 = [&]() -> const scpp::vector_t<scpp::hash_t<scpp::unique_p<scpp::int_t<>>, scpp::string_t>>* { return &keyed_root_0; }; if (!scpp::keyed_ready(keyed_resolve_0_0(), false)) { return scpp::bool_t{false}; } scpp::int_t<> keyed_key_0_0 = static_cast<scpp::int_t<>>(0LL); auto keyed_resolve_0_1 = [&]() -> const scpp::hash_t<scpp::unique_p<scpp::int_t<>>, scpp::string_t>* { return scpp::keyed_child(keyed_resolve_0_0(), keyed_key_0_0, false); }; if (!scpp::keyed_ready(keyed_resolve_0_1(), false)) { return scpp::bool_t{false}; } scpp::string_t keyed_key_0_1 = scpp::string_t("x"); return scpp::keyed_probe(keyed_resolve_0_1(), keyed_key_0_1, false); }())).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
