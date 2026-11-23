#include <scpp/async_timer.hpp>
#include <scpp/async/frame.hpp>

#include <chrono>
#include <stdexcept>
#include <thread>
#include <utility>

namespace scpp::async {
namespace {
// Direct awaits form a single chain. Each resume hands the loop one next frame,
// optionally after a timer. Nested synchronous waits install their own loop.
struct event_loop {
    static thread_local event_loop* current;
    event_loop* previous = std::exchange(current, this);
    std::coroutine_handle<> next{};
    int64 delay = 0;

    ~event_loop() { current = previous; }

    void run(std::coroutine_handle<> root) {
        next = root;
        while (!root.done()) {
            auto frame = std::exchange(next, {});
            if (!frame) throw std::logic_error("async task suspended without a continuation");
            // Bound conversion to the host clock even for INT64_MAX milliseconds.
            while (delay > 0) {
                constexpr int64 day = 86400000;
                const auto chunk = delay < day ? delay : day;
                std::this_thread::sleep_for(std::chrono::milliseconds{chunk});
                delay -= chunk;
            }
            frame.resume();
        }
    }
};

thread_local event_loop* event_loop::current = nullptr;

event_loop& active_loop() {
    if (!event_loop::current) throw std::logic_error("async await requires an active scheduler");
    return *event_loop::current;
}
}

void timer::await_suspend(std::coroutine_handle<> continuation) const {
    auto& loop = active_loop();
    loop.delay = milliseconds;
    loop.next = continuation;
}

void detail::schedule_child(std::coroutine_handle<> child) {
    active_loop().next = child;
}

void detail::schedule_continuation(std::coroutine_handle<> parent) noexcept {
    event_loop::current->next = parent;
}

void detail::run_until_complete(std::coroutine_handle<> root) {
    event_loop loop;
    loop.run(root);
}
}
