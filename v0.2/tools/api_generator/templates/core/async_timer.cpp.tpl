#pragma once
#include "@scalar_header@"
#include <coroutine>

namespace scpp::async {
struct timer {
    int64 milliseconds;
    bool await_ready() const noexcept { return milliseconds <= 0; }
    void await_suspend(std::coroutine_handle<> continuation) const;
    void await_resume() const noexcept {}
};

inline timer delay_ms(int64 milliseconds) noexcept { return {milliseconds}; }
}
