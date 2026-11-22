#include <scpp/fixed_array.hpp>
#include <stdexcept>

namespace scpp {
void throw_fixed_array_bounds() {
    throw std::out_of_range("sequence_index_out_of_range");
}
}
