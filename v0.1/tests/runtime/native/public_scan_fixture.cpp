#include "modules/filesystem/filesystem.hpp"
#include "scpp/detail/concrete_keys.hpp"
#include "scpp/detail/concrete_access.hpp"
#include "scpp/vector_string.hpp"
#include <type_traits>

extern void begin_allocations();
extern unsigned end_allocations();
unsigned native_scan_allocations(const char *path)
{
    scpp::string_t input(path);
    begin_allocations();
    auto result = scpp::fs::scan(input);
    auto count = end_allocations();
    if (!result.has_value().native_value()) { std::abort(); }
    return count;
}
bool typed_hash_proof()
{
    using scpp::string;
    using scpp::int64;
    scpp::hash_t<int64, string> values;
    string key("a\0b", scpp::uint64(3));
    values.set(key, int64(17));
    key.clear();
    string same("a\0b", scpp::uint64(3));
    if (!values.has(same).native_value() || values.at(same).native_value() != 17) return false;
    auto copy = values;
    copy.set(same, int64(23));
    if (values.at(same).native_value() != 17) return false;
    const auto &native = scpp::detail::string_access::get(same);
    return scpp::hash_detail::key_ops<string>::hash(same) == scpp::hash_detail::key_ops<scpp::string_t>::hash(native);
}
