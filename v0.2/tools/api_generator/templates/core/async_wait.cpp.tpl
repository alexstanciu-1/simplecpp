#pragma once
#include "@scalar_header@"

#include "scpp/async_fwd.hpp"

namespace scpp::async {
int64 wait_for(int64_task (*factory)());
}
