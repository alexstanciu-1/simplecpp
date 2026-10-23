#include "modules/tasks/tasks.hpp"
#include "scpp/callable_t.hpp"
#include <atomic>
#include <barrier>
#include <cassert>
#include <chrono>
#include <memory>
#include <stdexcept>
#include <string>
#include <thread>

using integer = scpp::int_t<>;
namespace tasks = scpp::tasks;
struct retained_owner {
	std::atomic<int>& destroyed;
	explicit retained_owner(std::atomic<int>& count) : destroyed(count) {}
	~retained_owner() { ++destroyed; }
};

// Both callbacks own source-shaped shared handles. Observer references below are
// test instrumentation, not a claim that borrowed source captures are admitted.
void verify(int pool_size, int failure) {
	tasks::configure_default_worker_pool(integer(pool_size));
	std::atomic<int> destroyed{0}, entered{0}, finished{0}, publications{0};
	std::barrier resident(2);
	scpp::shared_p<retained_owner> work_owner(std::make_shared<retained_owner>(destroyed));
	scpp::shared_p<retained_owner> publish_owner(std::make_shared<retained_owner>(destroyed));
	std::weak_ptr<retained_owner> work_lifetime = work_owner.native_value();
	std::weak_ptr<retained_owner> publish_lifetime = publish_owner.native_value();
	const auto caller = std::this_thread::get_id();
	scpp::callable_t<integer(integer)> work([work_owner, &resident, &entered, &finished, caller, failure](integer input) {
		assert(std::this_thread::get_id() != caller);
		assert(work_owner->destroyed.load() == 0);
		++entered;
		resident.arrive_and_wait();
		struct completion { std::atomic<int>& count; ~completion() { ++count; } } done{finished};
		if (input.native_value() == 1) {
			std::this_thread::sleep_for(std::chrono::milliseconds(25));
		}
		if (failure == 1 && input.native_value() == 0) { throw std::runtime_error("work failed"); }
		return input;
	}, scpp::task_permissions::joined_shared);
	scpp::callable_t<void(integer)> publish([publish_owner, &publications, caller, failure](integer input) {
		assert(std::this_thread::get_id() != caller);
		assert(publish_owner->destroyed.load() == 0);
		++publications;
		if (failure == 2 && input.native_value() == 0) { throw std::runtime_error("publish failed"); }
	}, scpp::task_permissions::joined_shared);
	work_owner.reset(); publish_owner.reset();
	assert(!work_lifetime.expired() && !publish_lifetime.expired());
	scpp::vector_t<integer> items;
	items.append(integer(0)); items.append(integer(1));
	bool caught = false;
	try {
		auto count = tasks::run_publish_unordered(items, integer(2), std::move(work), std::move(publish));
		assert(failure == 0 && count.native_value() == 2);
	} catch (const std::exception& error) {
		caught = true;
		assert(failure != 0);
		assert(std::string(error.what()).find(failure == 1 ? "work failed" : "publish failed") != std::string::npos);
	}
	assert(caught == (failure != 0));
	assert(entered == 2 && finished == 2); // All resident work completed before return/rethrow.
	assert(work_lifetime.expired() && publish_lifetime.expired() && destroyed == 2);
	assert(!work.transfer_allowed(scpp::task_policy::joined_shared));
	assert(!publish.transfer_allowed(scpp::task_policy::joined_shared));
	assert(publications >= 1); // Failure does not promise rollback of publication.
	int recovered = 0;
	auto count = tasks::run_publish_unordered(items, integer(2), [](integer input) { return input; },
		[&](integer) { ++recovered; });
	assert(count.native_value() == 2 && recovered == 2);
	tasks::shutdown_default_worker_pool();
}
int main() {
	for (int pool_size : {0, 2}) {
		for (int failure : {0, 1, 2}) { verify(pool_size, failure); }
	}
}
