// Native codec adapters for field-derived Simple C++ data records.
#ifndef SCPP_DETAIL_STRUCTURE_RECORD_FIELDS_HPP
#define SCPP_DETAIL_STRUCTURE_RECORD_FIELDS_HPP
#include <string_view>
#include <type_traits>
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

struct describe {
    template<class Count, class At, class Append>
    static constexpr auto sequence(Count count, At at, Append append) {
        return sequence_ops<Count, At, Append>{count, at, append};
    }
    template<class Record, class Value, class Default, class Shape = leaf>
    constexpr auto operator()(std::string_view name, Value Record::*member, Default fallback, Shape shape = {}) const {
        return field<Record, Value, Default, Shape>{name, member, fallback, shape};
    }
};

}
// The callback borrows each stored value for the duration of its invocation.
template<class Record, class Visitor> void visit_fields(const Record& record, Visitor&& visitor) {
    static_assert(std::is_same_v<Record, typename Record::scpp_escape_schema_owner>);
    Record::scpp_escape_data_schema(detail::describe{}, [&](auto... fields) {
        (visitor(fields.name, record.*fields.member, fields.shape), ...);
    });
}

template<class Record, class Visitor> void visit(const Record& record, Visitor&& visitor) {
    visit_fields(record, [&](auto name, const auto& value, auto) { visitor(name, value); });
}

}
#endif
