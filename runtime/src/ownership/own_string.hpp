#pragma once
#include <string>

namespace scpp {
class string;
namespace detail {
// Transfer a completed native buffer into an independently owned runtime string.
string own_string(std::string&& bytes);
}
}
