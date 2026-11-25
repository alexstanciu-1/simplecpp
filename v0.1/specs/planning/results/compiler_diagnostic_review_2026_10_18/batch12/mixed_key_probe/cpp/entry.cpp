#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/keyed_path.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<scpp::int_t<>> local_v = ([&]() -> scpp::vector_t<scpp::int_t<>> {
scpp::initializer_buffer<scpp::int_t<>> initializer_0(1);
{
scpp::int_t<> initializer_value_1 = static_cast<scpp::int_t<>>(1LL);
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<scpp::int_t<>>>::from_list(initializer_0.view());
}());
	scpp::mixed_t local_k = scpp::mixed_t{static_cast<scpp::int_t<>>(0LL)};
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((([&]() -> scpp::bool_t { const auto& keyed_root_2 = local_v; auto keyed_resolve_2_0 = [&]() -> const scpp::vector_t<scpp::int_t<>>* { return &keyed_root_2; }; if (!scpp::keyed_ready(keyed_resolve_2_0(), false)) { return scpp::bool_t{false}; } scpp::mixed_t keyed_key_2_0 = local_k; return scpp::keyed_probe(keyed_resolve_2_0(), keyed_key_2_0, false); }())).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
