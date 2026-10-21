#include "modules/tasks/tasks.hpp"
#include <cassert>
#include <latch>
#include <iostream>

using namespace scpp;
using integer = int_t<>;

void verify(int pool_size) {
	tasks::configure_default_worker_pool(integer(pool_size));
	const auto coordinator = std::this_thread::get_id();
	vector_t<integer> items;
	items.append(integer(0)); items.append(integer(1));
	auto fail = [](integer) -> integer { throw std::runtime_error("worker failure"); };
	int handlers = 0;
	auto replace = [&](integer n, shared_p<tasks::error>) -> integer {
		assert(std::this_thread::get_id() == coordinator);
		++handlers;
		// All configured pool workers may be waiting on this coordinator callback.
		vector_t<integer> nested; nested.append(n);
		auto result = tasks::run(nested, integer(2), [](integer v) { return integer(v.native_value()+40); });
		return result.at(0);
	};
	auto result = tasks::run(items, integer(2), fail, null, null, replace, integer(0));
	assert(handlers == 2 && result.at(0).native_value() == 40 && result.at(1).native_value() == 41);
	hash_t<integer,string_t> keyed; keyed.set(string_t("a"),integer(1));
	auto keyed_result = tasks::run(keyed,integer(2),fail,null,null,replace,integer(0));
	assert(keyed_result[string_t("a")].native_value()==41);
	auto omit = [&](integer,shared_p<tasks::error>) {
		assert(std::this_thread::get_id()==coordinator); ++handlers;
	};
	auto omitted = tasks::run(items,integer(2),fail,null,null,omit,integer(0));
	assert(omitted.size()==0);
	for (bool try_lock : {false, true}) {
		tasks::configure_publish_try_lock(bool_t(try_lock));
		std::vector<std::int64_t> published;
		auto publish = [&](vector_t<integer> batch) {
			for (const auto &n : batch.native_value()) { published.push_back(n.native_value()); }
		};
		auto count = tasks::run_publish(items, integer(2), [](integer n) -> integer {
			if (n.native_value() == 0) { throw std::runtime_error("omit first"); }
			return integer(42);
		}, publish, omit, integer(0), integer(1));
		assert(count.native_value() == 1 && published == std::vector<std::int64_t>{42});
		published.clear();
		count = tasks::run_publish(items, integer(2), [](integer) -> integer { throw 7; },
			publish, replace, integer(0), integer(1));
		assert(count.native_value() == 2 && published == (std::vector<std::int64_t>{40, 41}));
	}
	tasks::configure_publish_try_lock(bool_t(false));
	int attempted=0;
	bool rejected=false;
	try {
		(void)tasks::run(items,integer(1),[&](integer)->integer {++attempted;throw std::runtime_error("worker");},null,null,
			[&](integer,shared_p<tasks::error>)->integer {assert(std::this_thread::get_id()==coordinator);throw std::runtime_error("handler");},integer(0));
	} catch(const std::exception& e) {rejected=std::string(e.what())=="handler";}
	assert(rejected && attempted==1);
	vector_t<integer> one;one.append(integer(0));
	auto timeout_result=tasks::run(one,integer(1),[](integer n) {
		std::this_thread::sleep_for(std::chrono::milliseconds(20));return n;
	},null,null,[&](integer,shared_p<tasks::error> e) {
		assert(std::this_thread::get_id()==coordinator && e->timeout.native_value());return integer(44);
	},integer(1));
	assert(timeout_result.at(0).native_value()==44);
	std::thread::id background_handler;
	auto batch=tasks::start(one,integer(1),fail,[&](integer) {
		assert(std::this_thread::get_id()==background_handler && background_handler!=coordinator);return integer(0);
	},vector_t<integer>{},[&](integer,shared_p<tasks::error>) {background_handler=std::this_thread::get_id();return integer(45);},integer(0));
	(void)tasks::join(batch); (void)tasks::join(batch);
	assert(tasks::done(batch).native_value());
	auto failed_batch = tasks::start(one, integer(1), fail, null, null,
		[](integer, shared_p<tasks::error>) -> integer { throw std::runtime_error("background handler"); }, integer(0));
	for (int attempt = 0; attempt < 2; ++attempt) {
		bool failed = false;
		try { (void)tasks::join(failed_batch); }
		catch (const std::exception &e) { failed = std::string(e.what()) == "background handler"; }
		assert(failed);
	}
	std::latch started(1), release(1);
	auto cancelled=tasks::start(one,integer(1),[&](integer)->integer {started.count_down();release.wait();throw std::runtime_error("cancelled work");},null,null,
		[](integer,shared_p<tasks::error>){return integer(46);},integer(0));
	started.wait();tasks::cancel(cancelled);release.count_down();(void)tasks::join(cancelled);
	assert(tasks::done(cancelled).native_value());
	std::atomic<bool> drained{false};
	(void)tasks::start(one,integer(1),fail,null,null,[&](integer,shared_p<tasks::error>){drained=true;return integer(47);},integer(0));
	tasks::shutdown();assert(drained.load());
}
int main(){verify(0);verify(2);std::cout<<"Coordinator dispatch, omission, nesting, timeout, cancellation and shutdown passed\n";}
