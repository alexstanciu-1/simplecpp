#include <scpp/entry.hpp>
#include <stdexcept>

namespace scpp {
int entry_exit_checked(::int64_t value)
{
    if (value < INT_MIN || value > INT_MAX) {
        throw std::out_of_range("Entry return is outside native int range");
    }
    return static_cast<int>(value);
}
}
