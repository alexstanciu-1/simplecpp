#include "scpp/json.hpp"
#include <cstdio>
int main() {
    const auto parsed = scpp::json::decode(scpp::string{R"({"name":"Ada","values":[null,false,42]})"});
    if (!parsed.has_value().native_value()) return 1;
    const auto rendered = scpp::json::encode(parsed.value());
    if (!rendered.has_value().native_value()) return 2;
    std::puts(rendered.value().data());
    // Malformed data is a checked result; this subsequent call remains independent.
    const auto limited = scpp::json::decode_mixed(scpp::string{R"({"2147483648":0})"});
    return limited.has_error().native_value() ? 0 : 3;
}
