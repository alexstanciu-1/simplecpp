#pragma once
#include "mixed_value.hpp"
#include "dynamic_value.hpp"
#include <string>
#include <string_view>
#include <unordered_set>
namespace scpp::detail::json {
// Private module transport. Only module failures populate error; allocation and
// unrelated runtime failures propagate. Public result construction is generated.
template<class Value> struct outcome { Value value; std::string error; };
class writer {
    std::string output_;
    std::unordered_set<const void*> active_;
    template<class Value> void table(const Value& value);
public:
    writer() { output_.reserve(64); }
    void append(std::string_view bytes) { output_.append(bytes); }
    void write(std::nullptr_t);
    void write(bool value);
    void write(std::int64_t value);
    void write(double value);
    void write(std::string_view value);
    template<boxed_family Family> void write(const boxed_value<Family>& value);
    std::string finish() && { return std::move(output_); }
};
outcome<std::string> encode_record(const void* value, void (*visit)(const void*, writer&));
outcome<dynamic_value> decode(std::string_view input);
outcome<mixed_value> decode_mixed(std::string_view input);
outcome<std::string> encode(const mixed_value& value);
outcome<std::string> encode(const dynamic_value& value);
outcome<std::string> encode_null();
outcome<std::string> encode(std::int64_t value);
outcome<std::string> encode(double value);
outcome<std::string> encode(bool value);
outcome<std::string> encode(std::string_view value);
}
