#include "int_task_private.hpp"
#include "scpp/async_core.hpp"

#include <stdexcept>
#include <utility>

namespace async_proof {
using scpp::async_core::scheduler;

int_task int_task::promise_type::get_return_object() noexcept {
    return int_task(handle::from_promise(*this));
}

void int_task::promise_type::unhandled_exception() noexcept {
    failure = std::current_exception();
}

void int_task::promise_type::completion::await_suspend(handle finished) const noexcept {
    if (auto parent = finished.promise().continuation)
        scheduler::current()->enqueue(parent);
}

int_task::int_task(int_task&& other) noexcept : frame_(std::exchange(other.frame_, {})) {}

int_task::~int_task() {
    if (frame_) frame_.destroy();
}

bool int_task::await_ready() const noexcept {
    return !frame_ || frame_.done();
}

void int_task::await_suspend(std::coroutine_handle<> parent) {
    auto* loop = scheduler::current();
    if (!loop) throw std::logic_error("async task requires an active scheduler");
    frame_.promise().continuation = parent;
    loop->enqueue(frame_);
}

int int_task::await_resume() const {
    if (!frame_ || !frame_.done()) throw std::logic_error("async task has no completed result");
    const auto& promise = frame_.promise();
    if (promise.failure) std::rethrow_exception(promise.failure);
    return promise.value;
}

void timer::await_suspend(std::coroutine_handle<> continuation) const {
    scpp::async_core::sleep_for(std::chrono::milliseconds{milliseconds}).await_suspend(continuation);
}

int sync_wait(int_task root) {
    scheduler loop;
    loop.enqueue(root.frame_);
    loop.run_until([&root] { return root.await_ready(); });
    return root.await_resume();
}
}
