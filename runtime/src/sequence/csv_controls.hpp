#pragma once
#include "scpp/value_error.hpp"
#include <string_view>

namespace scpp::detail {
struct csv_controls {
    char separator;
    char enclosure;
    std::string_view escape;

    csv_controls(std::string_view sep, std::string_view enc, std::string_view esc)
        : separator{}, enclosure{}, escape(esc) {
        if (sep.size() != 1) throw ValueError("CSV separator must be one byte");
        if (enc.size() != 1) throw ValueError("CSV enclosure must be one byte");
        if (esc.size() > 1) throw ValueError("CSV escape must be empty or one byte");
        separator = sep.front();
        enclosure = enc.front();
    }
    bool escapes(char ch) const { return !escape.empty() && escape.front() == ch; }
};
}
