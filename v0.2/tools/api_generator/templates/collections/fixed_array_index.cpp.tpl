#pragma once
#include <stdint.h>

namespace scpp {
[[noreturn]] void throw_fixed_array_bounds();
inline int64_t fixed_array_index(int64_t index, int64_t extent) {
    if (index < 0 || index >= extent) throw_fixed_array_bounds();
    return index;
}
}
