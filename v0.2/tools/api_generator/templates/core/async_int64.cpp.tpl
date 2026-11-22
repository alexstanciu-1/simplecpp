#pragma once

#include "@scalar_header@"
#include <coroutine>
#include <exception>

namespace scpp::async {
class int64_task final {
public:
    struct promise_type;
    using handle = std::coroutine_handle<promise_type>;

    struct promise_type final {
        int64 value{};
        std::exception_ptr failure;
        std::coroutine_handle<> continuation;

        int64_task get_return_object() noexcept;
        std::suspend_always initial_suspend() const noexcept { return {}; }
        struct completion {
            bool await_ready() const noexcept { return false; }
            void await_suspend(handle finished) const noexcept;
            void await_resume() const noexcept {}
        };
        completion final_suspend() const noexcept { return {}; }
        void return_value(int64 result) noexcept { value = result; }
        void unhandled_exception() noexcept;
    };

    int64_task(int64_task&& other) noexcept;
    ~int64_task();
    bool await_ready() const noexcept;
    void await_suspend(std::coroutine_handle<> parent);
    int64 await_resume() const;

private:
    explicit int64_task(handle frame) noexcept : frame_(frame) {}
    handle frame_;
    friend int64 sync_wait(int64_task root);
};

struct timer {
    int64 milliseconds;
    bool await_ready() const noexcept { return milliseconds <= 0; }
    void await_suspend(std::coroutine_handle<> continuation) const;
    void await_resume() const noexcept {}
};

inline timer delay_ms(int64 milliseconds) noexcept { return {milliseconds}; }
int64 sync_wait(int64_task root);
}
