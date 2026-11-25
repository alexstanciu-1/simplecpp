#pragma once

#include "../int_task_private.hpp"

namespace async_proof {
using scpp::async_core::sync_wait;

inline auto delay_ms(int milliseconds) noexcept {
    return scpp::async_core::sleep_for(std::chrono::milliseconds{milliseconds});
}
}
