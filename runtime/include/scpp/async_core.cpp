#include "scpp/async_core.hpp"

namespace scpp::async_core {

thread_local scheduler *scheduler::current_ = nullptr;

void scheduler::enqueue(std::coroutine_handle<> handle)
{
	if (handle != nullptr && !handle.done()) {
		std::lock_guard<std::mutex> lock(mutex_);
		ready_.push_back(handle);
		wake_.notify_one();
	}
}

void scheduler::schedule_at(clock::time_point when, std::coroutine_handle<> handle)
{
	if (handle != nullptr && !handle.done()) {
		std::lock_guard<std::mutex> lock(mutex_);
		timers_.push(timer_entry{when, next_timer_order_++, handle});
		wake_.notify_one();
	}
}

void scheduler::run()
{
	scheduler_scope scope(*this);
	while (run_one(false)) {
	}
}

bool scheduler::empty() const
{
	std::lock_guard<std::mutex> lock(mutex_);
	return ready_.empty() && timers_.empty();
}

scheduler *scheduler::current() noexcept
{
	return current_;
}

scheduler::scheduler_scope::scheduler_scope(scheduler &value) noexcept
	: previous_(current_)
{
	current_ = &value;
}

scheduler::scheduler_scope::~scheduler_scope()
{
	current_ = previous_;
}

bool scheduler::run_one(bool wait_when_empty)
{
	std::coroutine_handle<> next = nullptr;
	{
		std::unique_lock<std::mutex> lock(mutex_);
		enqueue_due_timers(clock::now());
		while (ready_.empty()) {
			if (!timers_.empty()) {
				const auto wake_at = timers_.top().when;
				wake_.wait_until(lock, wake_at);
				enqueue_due_timers(clock::now());
				continue;
			}
			if (!wait_when_empty) {
				return false;
			}
			wake_.wait(lock);
			enqueue_due_timers(clock::now());
		}
		next = ready_.front();
		ready_.pop_front();
	}

	if (next != nullptr && !next.done()) {
		next.resume();
	}
	return true;
}

void scheduler::enqueue_due_timers(clock::time_point now)
{
	while (!timers_.empty() && timers_.top().when <= now) {
		auto entry = timers_.top();
		timers_.pop();
		if (entry.handle != nullptr && !entry.handle.done()) {
			ready_.push_back(entry.handle);
		}
	}
}

void sleep_awaitable::await_suspend(std::coroutine_handle<> continuation) const
{
	auto *active_scheduler = scheduler::current();
	if (active_scheduler == nullptr) {
		throw runtime_error("async sleep used without an active async scheduler", "missing_async_scheduler", "scpp::async_core", "sleep_ms");
	}
	active_scheduler->schedule_at(scheduler::clock::now() + duration_, continuation);
}

sleep_awaitable sleep_for(std::chrono::milliseconds duration) noexcept
{
	return sleep_awaitable(duration);
}

sleep_awaitable sleep_ms(const int_t &duration_ms) noexcept
{
	const auto native_duration = duration_ms.native_value();
	if (native_duration <= 0) {
		return sleep_awaitable(std::chrono::milliseconds(0));
	}
	return sleep_awaitable(std::chrono::milliseconds(native_duration));
}

void yield_awaitable::await_suspend(std::coroutine_handle<> continuation) const
{
	auto *active_scheduler = scheduler::current();
	if (active_scheduler == nullptr) {
		throw runtime_error("async yield used without an active async scheduler", "missing_async_scheduler", "scpp::async_core", "yield_now");
	}
	active_scheduler->enqueue(continuation);
}

yield_awaitable yield_now() noexcept
{
	return yield_awaitable();
}

} // namespace scpp::async_core
