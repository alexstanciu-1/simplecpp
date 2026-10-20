#include "scpp/data_initializer.hpp"
#include "scpp/int_t.hpp"
#include "functional"
#include "scpp/vector_t.hpp"
#include "scpp/keyed_path.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::vector_t<std::function<scpp::int_t<>()>> local_v = ([&]() -> scpp::vector_t<std::function<scpp::int_t<>()>> {
scpp::initializer_buffer<std::function<scpp::int_t<>()>> initializer_0(1);
{
std::function<scpp::int_t<>()> initializer_value_1 = std::function<scpp::int_t<>()>{[]() -> scpp::int_t<> {
	return static_cast<scpp::int_t<>>(1LL);
}};
initializer_0.append(initializer_value_1);
}
return scpp::data_initializer<scpp::vector_t<std::function<scpp::int_t<>()>>>::from_list(initializer_0.view());
}());
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((([&]() -> scpp::bool_t { const auto& keyed_root_2 = local_v; auto keyed_resolve_2_0 = [&]() -> const scpp::vector_t<std::function<scpp::int_t<>()>>* { return &keyed_root_2; }; if (!scpp::keyed_ready(keyed_resolve_2_0(), false)) { return scpp::bool_t{false}; } scpp::int_t<> keyed_key_2_0 = static_cast<scpp::int_t<>>(0LL); const auto* keyed_leaf_2 = scpp::keyed_lookup(keyed_resolve_2_0(), keyed_key_2_0, false); if (!keyed_leaf_2) { return scpp::bool_t{false}; } return scpp::bool_t(scpp::php::condition_truthy((*keyed_leaf_2)).native_value()); }())).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
