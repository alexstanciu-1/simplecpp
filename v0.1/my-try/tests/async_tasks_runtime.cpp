#include "scpp/async_core.hpp"
#include "modules/tasks/tasks.hpp"
#include <atomic>
#include <barrier>
#include <cassert>
#include <iostream>
#include <memory>
#include <stdexcept>
#include <string>
#include <thread>

using integer = scpp::int_t<>;
namespace ac = scpp::async_core;
namespace tasks = scpp::tasks;

ac::task<std::string> owned_child(std::string value)
{
	co_await ac::yield_now();
	co_return value + "!";
}

ac::task<std::string> owned_parent(std::string value)
{
	co_return co_await owned_child(std::move(value));
}

ac::task<void> failure(std::shared_ptr<int> owner)
{
	co_await ac::yield_now();
	assert(*owner == 42);
	throw std::runtime_error("await failure");
}

int main()
{
	assert(ac::sync_wait(owned_parent(std::string(400, 'x'))) == std::string(400, 'x') + "!");
	auto lifetime = std::make_shared<int>(42);
	std::weak_ptr<int> weak = lifetime;
	try {
		ac::sync_wait(failure(std::move(lifetime)));
		assert(false);
	} catch (const std::runtime_error &error) {
		assert(std::string(error.what()) == "await failure");
	}
	assert(weak.expired());

	scpp::vector_t<integer> items;
	items.push_back(integer(1));
	items.push_back(integer(2));
	const auto coordinator = std::this_thread::get_id();
	std::barrier meet(2);
	std::atomic<int> calls{0};
	auto values = tasks::run(items, integer(2), [&](integer value) {
		assert(std::this_thread::get_id() != coordinator);
		++calls;
		meet.arrive_and_wait();
		return integer(value.native_value() * 2);
	});
	assert(calls == 2 && values[0].native_value() == 2 && values[1].native_value() == 4);

	auto capture = std::make_shared<int>(7);
	std::weak_ptr<int> background_lifetime = capture;
	auto batch = tasks::start(items, integer(2), [capture](integer value) {
		return integer(value.native_value() + *capture);
	});
	capture.reset();
	(void) tasks::join(batch);
	(void) tasks::join(batch);
	assert(static_cast<bool>(tasks::done(batch)));
	assert(background_lifetime.expired());

	int synchronized = 0;
	int published = 0;
	auto count = tasks::run_publish_unordered(items, integer(2), [&](integer value) {
		tasks::synchronize([&] {
			++synchronized;
			try { tasks::synchronize([] {}); assert(false); }
			catch (const std::logic_error &) {}
		});
		return value;
	}, [&](integer value) {
		published += static_cast<int>(value.native_value());
		try { tasks::synchronize([] {}); assert(false); }
		catch (const std::logic_error &) {}
	});
	assert(count.native_value() == 2 && synchronized == 2 && published == 3);
	try { tasks::synchronize([] {}); assert(false); }
	catch (const std::logic_error &) {}

	auto failed = tasks::start(items, integer(2), [](integer) -> integer {
		throw std::runtime_error("worker failure");
	});
	for (int attempt = 0; attempt != 2; ++attempt) {
		try { (void) tasks::join(failed); assert(false); }
		catch (const std::exception &error) {
			assert(std::string(error.what()).find("worker failure") != std::string::npos);
		}
	}
	tasks::shutdown_default_worker_pool();
	std::cout << "async ownership and task execution contracts passed\n";
}
