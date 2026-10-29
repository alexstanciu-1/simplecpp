#include "scpp/datetime.hpp"
#include <cstdio>
int main() {
    const auto parsed = scpp::dt::parse_iso_utc(scpp::string{"2024-02-29T12:34:56Z"});
    if (!parsed.has_value().native_value()) return 1;
    const auto rendered = scpp::dt::format_iso_utc(parsed.value());
    std::puts(rendered.data());
    return 0;
}
