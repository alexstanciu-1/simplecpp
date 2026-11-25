#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/keyed_path.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::unique_p<scpp::int_t<>>> local_v;
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((([&]() -> scpp::bool_t { const auto& keyed_root_0 = local_v; auto keyed_resolve_0_0 = [&]() -> const scpp::vector_t<scpp::unique_p<scpp::int_t<>>>* { return &keyed_root_0; }; if (!scpp::keyed_ready(keyed_resolve_0_0(), false)) { return scpp::bool_t{false}; } scpp::int_t<> keyed_key_0_0 = static_cast<scpp::int_t<>>(0LL); return scpp::keyed_probe(keyed_resolve_0_0(), keyed_key_0_0, false); }())).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
