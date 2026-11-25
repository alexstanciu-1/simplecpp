#pragma once

#include <coroutine>
#include <exception>

namespace async_proof {
class int_task final {
public:
    struct promise_type;
    using handle = std::coroutine_handle<promise_type>;

    struct promise_type final {
        int value{};
        std::exception_ptr failure;
        std::coroutine_handle<> continuation;

        int_task get_return_object() noexcept;
        std::suspend_always initial_suspend() const noexcept { return {}; }
        struct completion {
            bool await_ready() const noexcept { return false; }
            void await_suspend(handle finished) const noexcept;
            void await_resume() const noexcept {}
        };
        completion final_suspend() const noexcept { return {}; }
        void return_value(int result) noexcept { value = result; }
        void unhandled_exception() noexcept;
    };

    int_task(int_task&& other) noexcept;
    ~int_task();
    bool await_ready() const noexcept;
    void await_suspend(std::coroutine_handle<> parent);
    int await_resume() const;

private:
    explicit int_task(handle frame) noexcept : frame_(frame) {}
    handle frame_;
    friend int sync_wait(int_task root);
};

struct timer {
    int milliseconds;
    bool await_ready() const noexcept { return milliseconds <= 0; }
    void await_suspend(std::coroutine_handle<> continuation) const;
    void await_resume() const noexcept {}
};

inline timer delay_ms(int milliseconds) noexcept { return {milliseconds}; }
int sync_wait(int_task root);
}
