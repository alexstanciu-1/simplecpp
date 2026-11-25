#include "scpp/async_core.hpp"
#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"

#include <cassert>
#include <chrono>
#include <stdexcept>
#include <string>
#include <thread>
#include <vector>

namespace {

scpp::async_core::task<scpp::int_t<>> immediate_value()
{
	co_return scpp::int_t<>(7);
}

scpp::async_core::task<void> sleep_and_record(std::vector<int> &events, int value, int delay_ms)
{
	co_await scpp::async_core::sleep_ms(scpp::int_t<>(delay_ms));
	events.push_back(value);
}

scpp::async_core::task<void> yield_and_record(std::vector<int> &events, int before, int after)
{
	events.push_back(before);
	co_await scpp::async_core::yield_now();
	events.push_back(after);
}

scpp::async_core::task<scpp::int_t<>> nested_value()
{
	auto child = immediate_value();
	const auto value = co_await child;
	co_return scpp::int_t<>(value.native_value() + 5);
}

scpp::async_core::task<scpp::int_t<>> nested_ready_value()
{
	auto child = scpp::async_core::ready_task(scpp::int_t<>(20));
	const auto value = co_await child;
	co_await scpp::async_core::ready_task();
	co_return scpp::int_t<>(value.native_value() + 2);
}

scpp::async_core::task<void> failing_task()
{
	co_await scpp::async_core::sleep_ms(scpp::int_t<>(1));
	throw std::runtime_error("async failure");
}

scpp::async_core::task<void> record_two_timers(std::vector<int> &events)
{
	auto slower = sleep_and_record(events, 2, 10);
	auto faster = sleep_and_record(events, 1, 1);
	co_await faster;
	co_await slower;
}

scpp::async_core::task<void> record_two_yields(std::vector<int> &events)
{
	auto first = yield_and_record(events, 1, 3);
	auto second = yield_and_record(events, 2, 4);
	scpp::async_core::spawn(first);
	scpp::async_core::spawn(second);
	co_await first;
	co_await second;
}

class background_signal_awaitable final {
public:
	[[nodiscard]] bool await_ready() const noexcept
	{
		return false;
	}

	void await_suspend(std::coroutine_handle<> continuation)
	{
		auto *active_scheduler = scpp::async_core::scheduler::current();
		assert(active_scheduler != nullptr);
		worker_ = std::thread([active_scheduler, continuation]() {
			std::this_thread::sleep_for(std::chrono::milliseconds(1));
			active_scheduler->enqueue(continuation);
		});
	}

	void await_resume()
	{
		if (worker_.joinable()) {
			worker_.join();
		}
	}

private:
	std::thread worker_;
};

scpp::async_core::task<scpp::int_t<>> cross_thread_signal()
{
	co_await background_signal_awaitable();
	co_return scpp::int_t<>(99);
}

void test_immediate_value()
{
	const auto value = scpp::async_core::sync_wait(immediate_value());
	assert(value.native_value() == 7);
}

void test_nested_await()
{
	const auto value = scpp::async_core::sync_wait(nested_value());
	assert(value.native_value() == 12);
}

void test_ready_task()
{
	const auto value = scpp::async_core::sync_wait(scpp::async_core::ready_task(scpp::int_t<>(11)));
	assert(value.native_value() == 11);
	scpp::async_core::sync_wait(scpp::async_core::ready_task());
	const auto nested = scpp::async_core::sync_wait(nested_ready_value());
	assert(nested.native_value() == 22);
}

void test_sleep_order()
{
	std::vector<int> events;
	scpp::async_core::sync_wait(record_two_timers(events));
	assert(events.size() == 2);
	assert(events[0] == 1);
	assert(events[1] == 2);
}

void test_sleep_elapsed()
{
	std::vector<int> events;
	auto started = std::chrono::steady_clock::now();
	scpp::async_core::sync_wait(sleep_and_record(events, 1, 2));
	auto elapsed = std::chrono::steady_clock::now() - started;
	assert(elapsed >= std::chrono::milliseconds(1));
	assert(events.size() == 1);
}

void test_yield_order()
{
	std::vector<int> events;
	scpp::async_core::sync_wait(record_two_yields(events));
	assert(events.size() == 4);
	assert(events[0] == 1);
	assert(events[1] == 2);
	assert(events[2] == 3);
	assert(events[3] == 4);
}

void test_error_propagation()
{
	bool caught = false;
	try {
		scpp::async_core::sync_wait(failing_task());
	} catch (const std::runtime_error &error) {
		caught = std::string(error.what()) == "async failure";
	}
	assert(caught);
}

void test_cross_thread_wakeup()
{
	const auto value = scpp::async_core::sync_wait(cross_thread_signal());
	assert(value.native_value() == 99);
}

void test_missing_scheduler_diagnostic()
{
	auto awaitable = scpp::async_core::sleep_ms(scpp::int_t<>(1));
	bool caught = false;
	try {
		awaitable.await_suspend(std::noop_coroutine());
	} catch (const scpp::runtime_error &error) {
		caught = std::string(error.code()) == "missing_async_scheduler";
	}
	assert(caught);

	auto yield = scpp::async_core::yield_now();
	caught = false;
	try {
		yield.await_suspend(std::noop_coroutine());
	} catch (const scpp::runtime_error &error) {
		caught = std::string(error.code()) == "missing_async_scheduler";
	}
	assert(caught);

	auto task = immediate_value();
	caught = false;
	try {
		scpp::async_core::spawn(task);
	} catch (const scpp::runtime_error &error) {
		caught = std::string(error.code()) == "missing_async_scheduler";
	}
	assert(caught);
}

} // namespace

int main()
{
	test_immediate_value();
	test_nested_await();
	test_ready_task();
	test_sleep_order();
	test_sleep_elapsed();
	test_yield_order();
	test_error_propagation();
	test_cross_thread_wakeup();
	test_missing_scheduler_diagnostic();
	return 0;
}
