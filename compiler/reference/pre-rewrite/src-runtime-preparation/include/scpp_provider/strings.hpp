#pragma once

#include <scpp/lang/php.hpp>
#include <cstdint>

// Provider implementations, selected by exposure JSON. The preparation tool and
// compiler know only their declared signatures, borrowing and result contracts.
namespace scpp_provider {

// Preserve Simple C++'s strict whole-string decimal and checked-range policy.
inline std::int64_t int_from_string(const scpp::string_t &value) {
    return scpp::cast<scpp::int_t<>>(value).native_value();
}

// The public runtime operator creates an independent owned result.
inline scpp::string_t string_concat(const scpp::string_t &left, const scpp::string_t &right) {
    return left + right;
}

// Expose a language integer without assuming the target's std::size_t width.
// The checked conversion rejects a length outside the exposed integer range.
inline std::int64_t string_byte_length(const scpp::string_t &value) {
    return scpp::cast<scpp::int_t<>>(value.byte_size()).native_value();
}

} // namespace scpp_provider
