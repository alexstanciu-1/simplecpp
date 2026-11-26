// Private Linux compiler boundary for decimal literal preparation and C++ emission.
#pragma once
#include <string>

namespace scpp::compiler {
double parse_decimal_literal(const std::string& spelling);
std::string cpp_binary64_literal(double value);
}
