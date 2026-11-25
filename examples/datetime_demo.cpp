#include "scpp/datetime.hpp"
#include <cstdio>
#include <cstring>
int main() {
    const auto parsed = scpp::dt::parse_iso_utc(scpp::string{"2024-02-29T12:34:56Z"});
    if (!parsed.has_value().native_value()) return 1;
    const auto common = scpp::dt::parse_common_local(scpp::string{"2024-02-29T12:34:56Z"});
    if (!common.has_value().native_value() ||
        std::strcmp(scpp::dt::format_local(scpp::string{"U"},common.value()).data(),"1709210096") != 0) return 2;
    const auto rendered = scpp::dt::format_iso_utc(parsed.value());
    std::puts(rendered.data());
    return 0;
}
