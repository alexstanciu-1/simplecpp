#include <scpp/owner_access.hpp>
#include <stdexcept>

namespace scpp {
void throw_invalid_object_cast()
{
    throw std::runtime_error("invalid_object_cast");
}
void throw_empty_owner()
{
    throw std::runtime_error("ownership_empty_access");
}
}
