// Native codec adapters for field-derived Simple C++ data records.
#ifndef SCPP_DETAIL_STRUCTURE_RECORD_FIELDS_HPP
#define SCPP_DETAIL_STRUCTURE_RECORD_FIELDS_HPP
#include <string_view>
#include <type_traits>
#include <tuple>
#include <utility>

namespace scpp::structure {
namespace detail {
struct leaf {};
template<class Count, class At, class Append> struct sequence_ops {
    [[no_unique_address]] Count count;
    [[no_unique_address]] At at;
    [[no_unique_address]] Append append;
};

template<class Record, class Value, class Default, class Shape>
struct field {
    std::string_view name;
    Value Record::*member;
    [[no_unique_address]] Default fallback;
    [[no_unique_address]] Shape shape;
    using value_type = Value;
};

template<class Record, class Value, class Default>
constexpr auto describe(const char* name, Value Record::*member, Default fallback) {
    return field<Record, Value, Default, leaf>{name, member, fallback, {}};
}
template<class Record, class Value, class Default, class Count, class At, class Append>
constexpr auto describe(const char* name, Value Record::*member, Default fallback, Count count, At at, Append append) {
    using shape = sequence_ops<Count, At, Append>;
    return field<Record, Value, Default, shape>{name, member, fallback, {count, at, append}};
}

template<class Record, class Apply> decltype(auto) apply_schema(Apply apply) {
    static_assert(std::is_same_v<Record, typename Record::scpp_escape_schema_owner>);
    auto unpack = [](auto... parts) { return describe(parts...); };
    return std::apply([&](auto... fields) { return apply(std::apply(unpack, fields)...); },
                      Record::scpp_escape_data_schema());
}
}
// The callback borrows each stored value for the duration of its invocation.
template<class Record, class Visitor> void visit_fields(const Record& record, Visitor&& visitor) {
    detail::apply_schema<Record>([&](auto... fields) {
        (visitor(fields.name, record.*fields.member, fields.shape), ...);
    });
}

template<class Record, class Visitor> void visit(const Record& record, Visitor&& visitor) {
    visit_fields(record, [&](auto name, const auto& value, auto) { visitor(name, value); });
}

}
#endif
