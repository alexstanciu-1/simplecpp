// Native codec adapters for field-derived Simple C++ data records.
#pragma once
#include <optional>
#include <string_view>
#include <type_traits>
#include <utility>

namespace scpp::structure {
namespace detail {
template<class Record, class Value, class Default>
struct field {
    std::string_view name;
    Value Record::*member;
    [[no_unique_address]] Default fallback;
    using value_type = Value;
};

inline constexpr auto describe = []<class Record, class Value, class Default>(
    std::string_view name, Value Record::*member, Default fallback) {
    return field<Record, Value, Default>{name, member, fallback};
};

template<class Field> struct input {
    Field field;
    std::optional<typename Field::value_type> value;

    template<class Reader> bool select(std::string_view name, Reader& reader) {
        if (name != field.name) return false;
        if (value) reader.reject("duplicate field");
        value.emplace(reader.template read<typename Field::value_type>());
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
template<class Record, class Visitor> void visit(const Record& record, Visitor&& visitor) {
    static_assert(std::is_same_v<Record, typename Record::scpp_escape_schema_owner>);
    Record::scpp_escape_data_schema(detail::describe, [&](auto... fields) {
        (visitor(fields.name, record.*fields.member), ...);
    });
}

// Reader::fields consumes one object; reject must throw. The caller controls the
// document boundary so nested records can use the same adapter as the root.
template<class Record, class Reader, class Factory>
auto read(Reader& reader, Factory factory) {
    static_assert(std::is_same_v<Record, typename Record::scpp_escape_schema_owner>);
    return Record::scpp_escape_data_schema(detail::describe, [&](auto... fields) {
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
