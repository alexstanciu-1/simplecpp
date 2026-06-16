#include "scpp/async_core.hpp"

namespace scpp::async_core {

thread_local scheduler *scheduler::current_ = nullptr;

void scheduler::enqueue(std::coroutine_handle<> handle)
{
	if (handle != nullptr && !handle.done()) {
		ready_.push_back(handle);
	}
}

void scheduler::schedule_at(clock::time_point when, std::coroutine_handle<> handle)
{
	if (handle != nullptr && !handle.done()) {
		timers_.push(timer_entry{when, next_timer_order_++, handle});
	}
}

void scheduler::run()
{
	scheduler_scope scope(*this);
	while (run_one()) {
	}
}

bool scheduler::empty() const
{
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

bool scheduler::run_one()
{
	enqueue_due_timers(clock::now());
	if (!ready_.empty()) {
		auto handle = ready_.front();
		ready_.pop_front();
		if (handle != nullptr && !handle.done()) {
			handle.resume();
		}
		return true;
	}

	if (timers_.empty()) {
		return false;
	}

	const auto now = clock::now();
	const auto next = timers_.top().when;
	if (next > now) {
		std::this_thread::sleep_until(next);
	}
	enqueue_due_timers(clock::now());
	return !ready_.empty();
}

void scheduler::enqueue_due_timers(clock::time_point now)
{
	while (!timers_.empty() && timers_.top().when <= now) {
		auto entry = timers_.top();
		timers_.pop();
		enqueue(entry.handle);
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

} // namespace scpp::async_core
