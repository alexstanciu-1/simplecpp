#pragma once
#include <coroutine>

namespace scpp::async::detail {
void schedule_child(std::coroutine_handle<> child);
void schedule_continuation(std::coroutine_handle<> parent) noexcept;
void run_until_complete(std::coroutine_handle<> root);
}
