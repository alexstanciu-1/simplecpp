#include "frontend_host.hpp"
#include "../../../06_native/binary64_literal.hpp"

namespace App {
scpp::string host_decimal_literal_(scpp::string spelling) {
    const auto value = scpp::compiler::parse_decimal_literal(
        std::string(spelling.data(), static_cast<std::size_t>(spelling.size())));
    const auto literal = scpp::compiler::cpp_binary64_literal(value);
    return scpp::string(literal.data(), literal.size());
}
}
