#pragma once
#include "mixed_value.hpp"
#include "dynamic_value.hpp"
#include <string>
#include <string_view>
namespace scpp::detail::json {
// Private module transport. Only module failures populate error; allocation and
// unrelated runtime failures propagate. Public result construction is generated.
template<class Value> struct outcome { Value value; std::string error; };
outcome<dynamic_value> decode(std::string_view input);
outcome<mixed_value> decode_mixed(std::string_view input);
outcome<std::string> encode(const mixed_value& value);
outcome<std::string> encode(const dynamic_value& value);
outcome<std::string> encode(std::int64_t value);
outcome<std::string> encode(double value);
outcome<std::string> encode(bool value);
outcome<std::string> encode(std::string_view value);
}
