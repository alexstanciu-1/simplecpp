// Native codec adapters for field-derived Simple C++ data records.
#pragma once
#include <optional>
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

template<class Field> struct input {
    Field field;
    std::optional<typename Field::value_type> value;

    template<class Reader> bool select(std::string_view name, Reader& reader) {
        if (name != field.name) return false;
        if (value) reader.reject("duplicate field");
        if constexpr (std::is_same_v<decltype(field.shape), leaf>)
            value.emplace(reader.template read<typename Field::value_type>());
        else
            reader.sequence(value.emplace(), field.shape);
        return true;
    }
    template<class Reader> void validate(Reader& reader) const {
        if constexpr (std::is_same_v<decltype(field.fallback), std::nullptr_t>)
            if (!value) reader.reject("missing required field");
    }
    void apply_default() {
        if constexpr (!std::is_same_v<decltype(field.fallback), std::nullptr_t>)
            if (!value) value.emplace(field.fallback());
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

// Reader::fields consumes one object; reject must throw. The caller controls the
// document boundary so nested records can use the same adapter as the root.
template<class Record, class Reader, class Factory>
auto read(Reader& reader, Factory factory) {
    static_assert(std::is_same_v<Record, typename Record::scpp_escape_schema_owner>);
    return Record::scpp_escape_data_schema(detail::describe{}, [&](auto... fields) {
        auto construct = [&](auto... slots) {
            reader.fields([&](std::string_view name) {
                if (!(slots.select(name, reader) || ...)) reader.reject("unknown field");
            });
            (slots.validate(reader), ...);
            (slots.apply_default(), ...);
            return factory(std::move(*slots.value)...);
        };
        return construct(detail::input<decltype(fields)>{fields, {}}...);
    });
}
}
