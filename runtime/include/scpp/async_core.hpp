#pragma once

#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"

#include <chrono>
#include <condition_variable>
#include <coroutine>
#include <cstddef>
#include <deque>
#include <exception>
#include <mutex>
#include <optional>
#include <queue>
#include <type_traits>
#include <utility>
#include <vector>

namespace scpp::async_core {

template <typename T>
class task;

enum class state {
	pending,
	running,
	suspended,
	completed,
	failed,
	cancelled
};

class scheduler final {
public:
	using clock = std::chrono::steady_clock;

	scheduler() = default;
	scheduler(const scheduler &) = delete;
	scheduler &operator=(const scheduler &) = delete;

	void enqueue(std::coroutine_handle<> handle);
	void schedule_at(clock::time_point when, std::coroutine_handle<> handle);
	void run();

	template <typename TPredicate>
	void run_until(TPredicate predicate)
	{
		scheduler_scope scope(*this);
		while (!predicate()) {
			if (!run_one(true)) {
				break;
			}
		}
	}

	[[nodiscard]] bool empty() const;
	[[nodiscard]] static scheduler *current() noexcept;

private:
	struct timer_entry final {
		clock::time_point when;
		std::size_t order = 0;
		std::coroutine_handle<> handle;
	};

	struct timer_compare final {
		[[nodiscard]] bool operator()(const timer_entry &left, const timer_entry &right) const noexcept
		{
			if (left.when == right.when) {
				return left.order > right.order;
			}
			return left.when > right.when;
		}
	};

	class scheduler_scope final {
	public:
		explicit scheduler_scope(scheduler &value) noexcept;
		~scheduler_scope();

		scheduler_scope(const scheduler_scope &) = delete;
		scheduler_scope &operator=(const scheduler_scope &) = delete;

	private:
		scheduler *previous_ = nullptr;
	};

	[[nodiscard]] bool run_one(bool wait_when_empty);
	void enqueue_due_timers(clock::time_point now);

	std::deque<std::coroutine_handle<>> ready_;
	std::priority_queue<timer_entry, std::vector<timer_entry>, timer_compare> timers_;
	mutable std::mutex mutex_;
	std::condition_variable wake_;
	std::size_t next_timer_order_ = 0;

	static thread_local scheduler *current_;
};

namespace detail {

template <typename T>
class task_promise_base {
public:
	std::exception_ptr exception = nullptr;
	std::coroutine_handle<> continuation = nullptr;
	scheduler *owner = nullptr;
	state current_state = state::pending;
	bool started = false;

	std::suspend_always initial_suspend() noexcept
	{
		current_state = state::suspended;
		return {};
	}

	struct final_awaitable final {
		[[nodiscard]] bool await_ready() const noexcept { return false; }

		template <typename TPromise>
		void await_suspend(std::coroutine_handle<TPromise> handle) const noexcept
		{
			auto &promise = handle.promise();
			promise.current_state = promise.exception ? state::failed : state::completed;
			if (promise.continuation != nullptr && promise.owner != nullptr) {
				promise.owner->enqueue(promise.continuation);
			}
		}

		void await_resume() const noexcept {}
	};

	[[nodiscard]] final_awaitable final_suspend() noexcept { return {}; }

	void unhandled_exception() noexcept
	{
		exception = std::current_exception();
	}
};

template <typename T>
class task_promise final : public task_promise_base<T> {
public:
	std::optional<T> value;

	[[nodiscard]] task<T> get_return_object() noexcept;

	void return_value(T result) noexcept(std::is_nothrow_move_constructible_v<T>)
	{
		value = std::move(result);
	}
};

template <>
class task_promise<void> final : public task_promise_base<void> {
public:
	[[nodiscard]] task<void> get_return_object() noexcept;

	void return_void() noexcept {}
};

} // namespace detail

template <typename T>
class task final {
public:
	using promise_type = detail::task_promise<T>;
	using handle_type = std::coroutine_handle<promise_type>;

	explicit task(handle_type handle) noexcept
		: handle_(handle)
	{
	}

	task(task &&other) noexcept
		: handle_(std::exchange(other.handle_, nullptr))
	{
	}

	task &operator=(task &&other) noexcept
	{
		if (this != &other) {
			destroy();
			handle_ = std::exchange(other.handle_, nullptr);
		}
		return *this;
	}

	task(const task &) = delete;
	task &operator=(const task &) = delete;

	~task()
	{
		destroy();
	}

	[[nodiscard]] bool valid() const noexcept { return handle_ != nullptr; }
	[[nodiscard]] bool done() const noexcept { return handle_ == nullptr || handle_.done(); }
	[[nodiscard]] state status() const noexcept
	{
		return handle_ == nullptr ? state::completed : handle_.promise().current_state;
	}

	void start(scheduler &owner)
	{
		if (handle_ == nullptr) {
			throw runtime_error("async task has no coroutine handle", "invalid_async_task", "scpp::async_core", "task::start");
		}
		auto &promise = handle_.promise();
		if (promise.started) {
			return;
		}
		promise.owner = &owner;
		promise.started = true;
		promise.current_state = state::running;
		owner.enqueue(handle_);
	}

	T result()
	{
		if (handle_ == nullptr || !handle_.done()) {
			throw runtime_error("async task result requested before completion", "async_task_not_complete", "scpp::async_core", "task::result");
		}
		auto &promise = handle_.promise();
		if (promise.exception) {
			std::rethrow_exception(promise.exception);
		}
		if constexpr (std::is_void_v<T>) {
			return;
		} else {
			return std::move(*promise.value);
		}
	}

	class awaiter final {
	public:
		explicit awaiter(task &owner) noexcept
			: owner_(owner)
		{
		}

		[[nodiscard]] bool await_ready() const noexcept
		{
			return owner_.done();
		}

		void await_suspend(std::coroutine_handle<> continuation)
		{
			auto *active_scheduler = scheduler::current();
			if (active_scheduler == nullptr) {
				throw runtime_error("await used without an active async scheduler", "missing_async_scheduler", "scpp::async_core", "task::await_suspend");
			}
			auto &promise = owner_.handle_.promise();
			promise.owner = active_scheduler;
			promise.continuation = continuation;
			if (!promise.started) {
				promise.started = true;
				promise.current_state = state::running;
				active_scheduler->enqueue(owner_.handle_);
			}
		}

		T await_resume()
		{
			return owner_.result();
		}

	private:
		task &owner_;
	};

	[[nodiscard]] awaiter operator co_await() noexcept
	{
		return awaiter(*this);
	}

private:
	void destroy() noexcept
	{
		if (handle_ != nullptr) {
			handle_.destroy();
			handle_ = nullptr;
		}
	}

	handle_type handle_ = nullptr;
};

namespace detail {

template <typename T>
task<T> task_promise<T>::get_return_object() noexcept
{
	return task<T>(std::coroutine_handle<task_promise<T>>::from_promise(*this));
}

inline task<void> task_promise<void>::get_return_object() noexcept
{
	return task<void>(std::coroutine_handle<task_promise<void>>::from_promise(*this));
}

} // namespace detail

class sleep_awaitable final {
public:
	explicit sleep_awaitable(std::chrono::milliseconds duration) noexcept
		: duration_(duration)
	{
	}

	[[nodiscard]] bool await_ready() const noexcept
	{
		return duration_.count() <= 0;
	}

	void await_suspend(std::coroutine_handle<> continuation) const;
	void await_resume() const noexcept {}

private:
	std::chrono::milliseconds duration_;
};

class yield_awaitable final {
public:
	[[nodiscard]] bool await_ready() const noexcept { return false; }

	void await_suspend(std::coroutine_handle<> continuation) const;
	void await_resume() const noexcept {}
};

[[nodiscard]] sleep_awaitable sleep_for(std::chrono::milliseconds duration) noexcept;
[[nodiscard]] sleep_awaitable sleep_ms(const int_t<> &duration_ms) noexcept;
[[nodiscard]] yield_awaitable yield_now() noexcept;

template <typename T>
task<T> ready_task(T value)
{
	co_return std::move(value);
}

inline task<void> ready_task()
{
	co_return;
}

template <typename T>
void spawn(task<T> &work)
{
	auto *active_scheduler = scheduler::current();
	if (active_scheduler == nullptr) {
		throw runtime_error("async task spawned without an active async scheduler", "missing_async_scheduler", "scpp::async_core", "spawn");
	}
	work.start(*active_scheduler);
}

template <typename T>
T sync_wait(task<T> root)
{
	scheduler event_loop;
	root.start(event_loop);
	event_loop.run_until([&root]() {
		return root.done();
	});
	return root.result();
}

} // namespace scpp::async_core
