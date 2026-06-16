#include "scpp/async_core.hpp"
#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"

#include <cassert>
#include <chrono>
#include <stdexcept>
#include <string>
#include <vector>

namespace {

scpp::async_core::task<scpp::int_t> immediate_value()
{
	co_return scpp::int_t(7);
}

scpp::async_core::task<void> sleep_and_record(std::vector<int> &events, int value, int delay_ms)
{
	co_await scpp::async_core::sleep_ms(scpp::int_t(delay_ms));
	events.push_back(value);
}

scpp::async_core::task<scpp::int_t> nested_value()
{
	auto child = immediate_value();
	const auto value = co_await child;
	co_return scpp::int_t(value.native_value() + 5);
}

scpp::async_core::task<void> failing_task()
{
	co_await scpp::async_core::sleep_ms(scpp::int_t(1));
	throw std::runtime_error("async failure");
}

scpp::async_core::task<void> record_two_timers(std::vector<int> &events)
{
	auto slower = sleep_and_record(events, 2, 10);
	auto faster = sleep_and_record(events, 1, 1);
	co_await faster;
	co_await slower;
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

void test_missing_scheduler_diagnostic()
{
	auto awaitable = scpp::async_core::sleep_ms(scpp::int_t(1));
	bool caught = false;
	try {
		awaitable.await_suspend(std::noop_coroutine());
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
	test_sleep_order();
	test_sleep_elapsed();
	test_error_propagation();
	test_missing_scheduler_diagnostic();
	return 0;
}
