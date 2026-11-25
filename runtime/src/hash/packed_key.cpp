#include "packed_key.hpp"
namespace scpp::detail {
// One compiled owner across payload instantiations and translation units.
packed_string_pool& packed_string_pool::instance() {
    static packed_string_pool pool;
    return pool;
}
}
