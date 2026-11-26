#include "frontend_host.hpp"
#include "../../../06_native/binary64_literal.hpp"

#include <scpp/exception.hpp>
#include <stdexcept>

namespace App {
double host_parse_decimal_(scpp::string spelling) {
    try {
        return scpp::compiler::parse_decimal_literal(std::string(spelling.data(), static_cast<std::size_t>(spelling.size())));
    } catch (const std::out_of_range& error) {
        scpp::throw_exception(scpp::make_exception(scpp::string(error.what(), std::char_traits<char>::length(error.what()))));
    }
}

scpp::string host_cpp_binary64_(double value) {
    const auto literal = scpp::compiler::cpp_binary64_literal(value);
    return scpp::string(literal.data(), literal.size());
}

scpp::string host_decimal_literal_(scpp::string spelling) {
    return host_cpp_binary64_(host_parse_decimal_(spelling));
}
}
