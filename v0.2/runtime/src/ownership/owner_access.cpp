#include <scpp/owner_access.hpp>
#include <stdexcept>

namespace scpp {
void throw_empty_owner()
{
    throw std::runtime_error("ownership_empty_access");
}
}
