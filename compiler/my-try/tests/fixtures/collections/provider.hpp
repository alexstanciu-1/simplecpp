#pragma once
#include "scpp/collections.hpp"
namespace collections_probe {
inline scpp::mixed_t mixed_input() {
 scpp::mixed_t out(std::make_unique<scpp::hash_t<scpp::mixed_t>>());
 out.table_if()->set(scpp::string_t("a"),scpp::mixed_t(scpp::int_t<>(7)));return out;
}
inline scpp::dynamic_t<> dynamic_input() {
 auto out=scpp::dynamic_t<>::make_container();
 out.get_table().set(scpp::string_t("a"),scpp::dynamic_t<>(scpp::int_t<>(8)));return out;
}
inline scpp::int_t<> dynamic_result(scpp::dynamic_t<> value) {
 for(auto entry:scpp::value_iteration(value)) return entry.value_copy().get_int();
 return scpp::int_t<>(-1);
}
}
