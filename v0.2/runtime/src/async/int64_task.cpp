#include <scpp/async_int64.hpp>

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

int64_task int64_task::promise_type::get_return_object() noexcept {
    return int64_task(handle::from_promise(*this));
}

void int64_task::promise_type::unhandled_exception() noexcept {
    failure = std::current_exception();
}

void int64_task::promise_type::completion::await_suspend(handle finished) const noexcept {
    if (auto parent = finished.promise().continuation) event_loop::current->next = parent;
}

int64_task::int64_task(int64_task&& other) noexcept : frame_(std::exchange(other.frame_, {})) {}

int64_task::~int64_task() {
    if (frame_) frame_.destroy();
}

bool int64_task::await_ready() const noexcept {
    return !frame_ || frame_.done();
}

void int64_task::await_suspend(std::coroutine_handle<> parent) {
    auto& loop = active_loop();
    frame_.promise().continuation = parent;
    loop.next = frame_;
}

int64 int64_task::await_resume() const {
    if (!frame_ || !frame_.done()) throw std::logic_error("async task has no completed result");
    const auto& promise = frame_.promise();
    if (promise.failure) std::rethrow_exception(promise.failure);
    return promise.value;
}

void timer::await_suspend(std::coroutine_handle<> continuation) const {
    auto& loop = active_loop();
    loop.delay = milliseconds;
    loop.next = continuation;
}

int64 sync_wait(int64_task root) {
    if (!root.frame_) throw std::logic_error("async wait requires a valid task");
    event_loop loop;
    loop.run(root.frame_);
    return root.await_resume();
}
}
