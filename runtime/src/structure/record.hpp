#pragma once
#include "record_fields.hpp"
#include <optional>

namespace scpp::structure {
namespace detail {
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

// Reader::fields consumes one object; reject must throw. The caller controls the
// document boundary so nested records can use the same adapter as the root.
template<class Record, class Reader, class Factory>
auto read(Reader& reader, Factory factory) {
    return detail::apply_schema<Record>([&](auto... fields) {
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
