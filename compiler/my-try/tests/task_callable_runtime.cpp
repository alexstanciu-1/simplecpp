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
