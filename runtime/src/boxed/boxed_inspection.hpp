#pragma once
#include "boxed_value.hpp"
#include "packed_table.hpp"
namespace scpp::detail {
// Private synchronous read access for module writers. No mutation callback or
// public cursor is exposed; the caller retains the source value for the visit.
struct boxed_inspection {
    template<boxed_family Family>
    static const auto& table(const boxed_value<Family>& value) {
        const auto* table = value.read_table();
        if (!table) throw std::runtime_error("boxed_expected_container");
        return *table;
    }
};
}
