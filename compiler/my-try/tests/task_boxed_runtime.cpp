#include "modules/tasks/tasks.hpp"
#include "scpp/dynamic_t.hpp"
#include <cassert>
#include <iostream>
#include <future>
using namespace scpp;
using integer = int_t<>;
struct dynamic_work {
	dynamic_t<> operator()(dynamic_t<> value) const { return value; }
};
template<typename T>
concept accepts_dynamic_tasks = requires(const T &items) {
	tasks::run(items,integer(1),dynamic_work{});
};
template<typename Box>
void verify_boxed_family() {
	using family=tasks::detail::boxed_task_family<Box>;
	auto items=family::empty();
	(void)family::output(items).append(Box(integer(42)));
	std::promise<void> started,release;
	auto ready=started.get_future();auto resume=release.get_future();
	auto handle=tasks::start(items,integer(1),[&](Box value) {
		started.set_value();resume.wait();return value;
	});
	ready.wait();family::output(items).set(integer(0),Box(integer(7)));release.set_value();
	auto first=tasks::join(handle);auto second=tasks::join(handle);
	assert(family::table(first)->at(integer(0)).get_int().native_value()==42);
	assert(family::table(second)->at(integer(0)).get_int().native_value()==42);
	auto keyed=family::empty();family::output(keyed).set(string_t("key"),Box(integer(9)));
	auto result=tasks::run(keyed,integer(1),[](Box value){return value;});
	assert(family::table(result)->at(string_t("key")).get_int().native_value()==9);
	auto voids=tasks::run(items,integer(1),[](Box){});
	assert(family::table(voids)->at(integer(0)).is_null().native_value());
	auto nested=family::empty();(void)family::output(nested).append(family::empty());
	bool ran=false,rejected=false;
	try { (void)tasks::start(nested,integer(1),[&](Box value){ran=true;return value;}); }
	catch(const runtime_error &e) { rejected=e.code()=="invalid_task_payload_transfer"; }
	assert(rejected && !ran);
	rejected=false;
	try { (void)tasks::run(items,integer(1),[](Box){return family::empty();}); }
	catch(const runtime_error &e) { rejected=e.code()=="invalid_task_payload_transfer"; }
	assert(rejected);
	auto recovered=tasks::run(items,integer(1),[](Box)->Box{throw std::runtime_error("fail");},
		null,null,[](Box,shared_p<tasks::error>){return Box(integer(8));},integer(0));
	assert(family::table(recovered)->at(integer(0)).get_int().native_value()==8);
	rejected=false;
	try { (void)tasks::run(items,integer(1),[](Box)->Box{throw std::runtime_error("fail");},
		null,null,[](Box,shared_p<tasks::error>){return family::empty();},integer(0)); }
	catch(const runtime_error &e) { rejected=e.code()=="invalid_task_payload_transfer"; }
	assert(rejected);
}
int main() {
	verify_boxed_family<mixed_t>();
	verify_boxed_family<dynamic_t<>>();
	bool shared_input_rejected=false;
	auto shared_table=shared<hash_t<mixed_t>>();
	(void)shared_table->append(mixed_t(integer(1)));
	try { (void)tasks::run(mixed_t(shared_table),integer(1),[](mixed_t value){return value;}); }
	catch(const runtime_error &e) { shared_input_rejected=e.code()=="invalid_task_input_transfer"; }
	assert(shared_input_rejected);
	hash_t<mixed_t> table;
	(void)table.append(mixed_t(integer(40)));
	(void)table.append(mixed_t(integer(42)));
	mixed_t items(unique<hash_t<mixed_t>>(std::move(table)));
	auto work=[](mixed_t value) { return value; };
	auto result=tasks::run(items,integer(1),work);
	assert(result.table_if()!=nullptr && result.table_if()->size()==2);
	std::atomic<const tasks::detail::batch_state *> observed{nullptr};
	auto background=tasks::start(items,integer(1),[&observed](mixed_t value,shared_p<tasks::context> context) {
		observed.store(context->state.get()); return value;
	});
	(void)tasks::join(background);
	const bool same_state=observed.load()==background->state.get();
	const auto completed=background->state->completed.load();
	const auto total=background->state->total.load();
	assert(same_state && total==2 && completed==2);
	for(bool associative : {false,true}) {
		auto input=unique<hash_t<mixed_t>>();
		if(associative) { input->set(mixed_t("a"),mixed_t(integer(1))); input->set(mixed_t("b"),mixed_t(integer(2))); }
		else { (void)input->append(mixed_t(integer(1))); (void)input->append(mixed_t(integer(2))); }
		std::promise<void> started,release;
		auto ready=started.get_future();auto resume=release.get_future();
		auto canceled=tasks::start(mixed_t(std::move(input)),integer(1),[&](mixed_t value) {
			started.set_value();resume.wait();return value;
		});
		ready.wait();tasks::cancel(canceled);release.set_value();
		auto partial=tasks::join(canceled);
		assert(partial.table_if()->size()==1);
		assert(canceled->state->total.load()==2 && canceled->state->completed.load()==1);
		assert(canceled->state->stop_requested.load());
	}
	bool rejects_scalar=false;
	try { (void)tasks::run(mixed_t(integer(1)),integer(1),work); }
	catch(const runtime_error &) { rejects_scalar=true; }
	assert(rejects_scalar);
	std::cout << "{\"mixed_basic\":true,\"rejects_scalar\":true,\"same_batch_state\":" << (same_state?"true":"false")
		<< ",\"total\":" << total << ",\"completed\":" << completed
		<< ",\"dynamic_overload\":" << (accepts_dynamic_tasks<dynamic_t<>>?"true":"false") << "}\n";
	auto dynamic_handle=shared<tasks::dynamic_batch>();
	dynamic_handle->coordinator=unique<tasks::detail::worker_thread>([dynamic_handle] {
		std::lock_guard<std::mutex> lock(dynamic_handle->lifecycle_mutex);
		dynamic_handle->result=dynamic_t<>::make_container();
		(void)dynamic_handle->result.get_table().append(dynamic_t<>(integer(42)));
		dynamic_handle->state->done.store(true);
	});
	tasks::detail::retain_background(dynamic_handle);
	assert(tasks::join(dynamic_handle).get_table().at(integer(0)).get_int().native_value()==42);
	assert(tasks::join(dynamic_handle).get_table().at(integer(0)).get_int().native_value()==42);
	assert(tasks::done(dynamic_handle).native_value());
	auto failed=shared<tasks::dynamic_batch>();
	failed->coordinator=unique<tasks::detail::worker_thread>([failed] {
		std::lock_guard<std::mutex> lock(failed->lifecycle_mutex);
		failed->error=std::make_exception_ptr(std::runtime_error("dynamic failure"));
		failed->state->done.store(true);
	});
	tasks::detail::retain_background(failed);
	int errors=0;
	for(int repeat=0;repeat<2;++repeat) {
		try { (void)tasks::join(failed); }
		catch(const std::runtime_error &e) { assert(std::string(e.what())=="dynamic failure");++errors; }
	}
	assert(errors==2);
	std::atomic<bool> drained=false;
	{
		auto dropped=shared<tasks::dynamic_batch>();
		dropped->coordinator=unique<tasks::detail::worker_thread>([dropped,&drained] {
			drained.store(true);dropped->state->done.store(true);
		});
		tasks::detail::retain_background(dropped);
	}
	tasks::shutdown();
	assert(drained.load());
}
