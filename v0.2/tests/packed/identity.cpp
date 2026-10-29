#include "packed_table.hpp"
std::uint32_t identity_from_other_translation_unit(std::string_view text) {
    scpp::detail::packed_table<std::string> other;
    other.set(text, "value");
    std::uint32_t identity = 0;
    other.visit_entries([&](auto code, const auto&) { identity = code; });
    return identity;
}
