#include "scpp/callable_t.hpp"
#include <cassert>
#include <memory>
#include <string>
#include <thread>

using callback = scpp::callable_t<int(int)>;
callback pass(callback value) { return value; }
void rejected(const callback& value) {
	bool failed = false;
	try { value.require_transfer(); }
	catch (const scpp::runtime_error& error) {
		failed = error.code() == "invalid_task_callback_transfer";
	}
	assert(failed);
}
int main() {
	callback empty;
	rejected(empty);
	callback allowed(std::function<int(int)>([n=40](int x) { return n+x; }), true);
	callback copied = pass(allowed);
	allowed = callback(std::function<int(int)>([](int x) { return x; }), false);
	rejected(allowed);
	copied.require_transfer();
	int result = 0;
	std::thread worker([copy=copied, &result] { copy.require_transfer(); result=copy(2); });
	worker.join(); assert(result == 42);
	callback moved = std::move(copied);
	moved.require_transfer(); assert(moved(1) == 41);
	rejected(copied);
	auto shared = std::make_shared<int>(7);
	callback blocked(std::function<int(int)>([shared](int x) { return *shared+x; }), false);
	callback blocked_copy = pass(blocked);
	rejected(blocked_copy);
	assert(blocked_copy(1) == 8); // Rejection is at transfer, not ordinary invocation.
	using scpp::task_permissions;
	using scpp::task_policy;
	callback joined(std::function<int(int)>([shared](int x) { return *shared+x; }), task_permissions::joined_shared);
	callback joined_copy = pass(joined);
	joined_copy.require_transfer(task_policy::joined_shared);
	rejected(joined_copy);
	assert(joined_copy(2) == 9);
	auto adapted = scpp::internal::carry_callback_transfer<int(int)>(
		[joined_copy](int x) { return joined_copy(x); }, joined_copy.transfer_permissions());
	callback erased(std::function<int(int)>(adapted), adapted.transfer_permissions());
	erased.require_transfer(task_policy::joined_shared);
	rejected(erased);
	assert(erased(3) == 10);
	callback joined_moved = std::move(erased);
	assert(!erased.transfer_allowed(task_policy::joined_shared));
	joined_moved.require_transfer(task_policy::joined_shared);
	rejected(joined_moved);
	callback false_claim(std::function<int(int)>(), task_permissions::isolated_and_joined);
	assert(!false_claim.transfer_allowed(task_policy::joined_shared));
	rejected(false_claim);
	assert(scpp::intersect_task_permissions(task_permissions::isolated_and_joined,
		task_permissions::joined_shared) == task_permissions::joined_shared);
	assert(scpp::intersect_task_permissions(task_permissions::joined_shared,
		task_permissions::none) == task_permissions::none);
	moved = blocked_copy;
	rejected(moved);
	callback good_again(std::function<int(int)>([](int x) { return x*2; }), true);
	moved = good_again;
	moved.require_transfer(); assert(moved(3) == 6);
	scpp::callable_t<void()> action(std::function<void()>([] {}), true);
	action.require_transfer(); action();
	scpp::callable_t<std::string(std::string)> text(std::function<std::string(std::string)>([](std::string x) { return x+"!"; }), true);
	text.require_transfer(); assert(text("owned") == "owned!");
}
