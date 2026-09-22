#include "test_common.hpp"
#include "scpp/process.hpp"
#include <chrono>
#include <thread>
#include <filesystem>
#include <fstream>
#include <signal.h>
#include <sys/wait.h>
#include <unistd.h>

using namespace scpp;
using proc = shared_p<process_handle>;
static proc launch(const char *script, const std::string &input = "", int timeout = 0) {
	vector_t<string_t> args{string_t("-c"), string_t(script)};
	auto r = process::start(string_t("/bin/sh"), args, string_t(input), int_t<>(timeout));
	assert(r.has_value().native_value());
	return r.value();
}
static shared_p<process_output> wait_for(proc handle) {
	auto deadline = std::chrono::steady_clock::now() + std::chrono::seconds(8);
	for (;;) {
		auto state = process::poll(handle);
		assert(state.has_value().native_value());
		if (state.value().native_value()) break;
		assert(std::chrono::steady_clock::now() < deadline);
		std::this_thread::sleep_for(std::chrono::milliseconds(2));
	}
	auto result = process::collect(handle);
	assert(result.has_value().native_value());
	return result.value();
}
static std::size_t descriptors() {
	return std::distance(std::filesystem::directory_iterator("/proc/self/fd"), std::filesystem::directory_iterator());
}
static bool exited(pid_t pid) {
	std::ifstream stat("/proc/" + std::to_string(pid) + "/stat");
	std::string row; std::getline(stat, row);
	return row.empty() || row.substr(row.rfind(')') + 2, 1) == "Z";
}
static void await_exit(pid_t pid) {
	const auto deadline = std::chrono::steady_clock::now() + std::chrono::seconds(3);
	while (!exited(pid)) {
		assert(std::chrono::steady_clock::now() < deadline);
		std::this_thread::sleep_for(std::chrono::milliseconds(2));
	}
}
// A private readiness file proves the descendant exists before a lifecycle action.
// No poll is needed to synchronize: polling itself may enforce the deadline.
struct ready_group {
	std::string directory;
	proc handle;
	pid_t leader = 0, descendant = 0;
	explicit ready_group(bool finish = false, int timeout = 0) {
		char pattern[] = "/tmp/scpp-process-test-XXXXXX";
		const auto path = mkdtemp(pattern);
		assert(path != nullptr);
		directory = path;
		vector_t<string_t> args{string_t("-c"),
			string_t(finish ? "sleep 30 & echo $$ $! > \"$1/ready\"; exit 7"
				: "sleep 30 & echo $$ $! > \"$1/ready\"; wait"),
			string_t("fixture"), string_t(directory)};
		auto result = process::start(string_t("/bin/sh"), args, string_t(""), int_t<>(timeout));
		assert(result.has_value().native_value());
		handle = result.value();
		const auto deadline = std::chrono::steady_clock::now() + std::chrono::seconds(3);
		for (;;) {
			std::ifstream ready(directory + "/ready");
			if (ready >> leader >> descendant) break;
			assert(std::chrono::steady_clock::now() < deadline);
			std::this_thread::sleep_for(std::chrono::milliseconds(2));
		}
		assert(leader > 0 && descendant > 0);
	}
	~ready_group() { handle = proc(); std::filesystem::remove_all(directory); }
};
int main() {
	const auto baseline = descriptors();
	{
		std::string bytes("a\0b\n", 4);
		auto h = launch("cat; printf problem >&2; exit 7", bytes);
		auto out = wait_for(h);
		assert(out->stdout_text.native_value() == bytes && out->stderr_text.native_value() == "problem");
		assert(out->exit_code.native_value() == 7 && out->signal.native_value() == 0);
		out->stdout_text = string_t("changed");
		assert(process::collect(h).value()->stdout_text.native_value() == bytes);
		auto alias = h;
		assert(process::close(h).value().native_value());
		assert(process::close(alias).value().native_value());
		assert(process::poll(alias).has_error().native_value());
		assert(process::collect(alias).has_error().native_value());
	}
	{
		auto h = launch("exit 127");
		assert(wait_for(h)->exit_code.native_value() == 127);
		vector_t<string_t> args;
		assert(process::start(string_t("sh"), args, string_t(""), int_t<>(0)).has_error().native_value());
		assert(process::start(string_t("/bin/sh"), args, string_t(""), int_t<>(-1)).has_error().native_value());
		assert(process::start(string_t("/missing-scpp-executable"), args, string_t(""), int_t<>(0)).has_error().native_value());
		assert(process::start(string_t("/bin/sh"), args, string_t(""), int_t<>(0), string_t("/missing-scpp-cwd")).has_error().native_value());
		args.append(string_t(std::string("a\0b", 3)));
		assert(process::start(string_t("/bin/sh"), args, string_t(""), int_t<>(0)).has_error().native_value());
	}
	{
		auto h = launch("sleep 5", "", 20);
		assert(!process::poll(h).value().native_value());
		assert(process::collect(h).has_error().native_value());
		auto out = wait_for(h);
		assert(out->timed_out.native_value() && !out->stopped.native_value());
		assert(out->signal.native_value() == SIGKILL);
	}
	{
		auto h = launch("sleep 5");
		assert(process::stop(h).value().native_value());
		assert(process::stop(h).value().native_value());
		auto out = wait_for(h);
		assert(out->stopped.native_value() && !out->timed_out.native_value());
	}
	{
		// Output exceeds typical pipe capacity before any stdin is consumed.
		std::string large(2 * 1024 * 1024, 'i');
		auto h = launch("head -c 1048576 /dev/zero; head -c 1048576 /dev/zero >&2; cat", large);
		auto out = wait_for(h);
		assert(out->stdout_text.native_value().size() == 1048576 + large.size());
		assert(out->stderr_text.native_value().size() == 1048576);
	}
	{
		vector_t<string_t> args{string_t("-c"), string_t("printf '%s:%s' \"$1\" \"$2\"; pwd"), string_t("arg0"), string_t(""), string_t("a b;$x")};
		auto h = process::start(string_t("/bin/sh"), args, string_t(""), int_t<>(0), string_t("/tmp")).value();
		assert(wait_for(h)->stdout_text.native_value() == ":a b;$x/tmp\n");
	}
	{
		// Each forced-cleanup path must reach descendants, not just the leader.
		for (int action = 0; action < 4; ++action) {
			ready_group group(false, action == 0 ? 1 : 0);
			assert(!exited(group.descendant));
			if (action == 0) {
				std::this_thread::sleep_for(std::chrono::milliseconds(5));
				auto out = wait_for(group.handle);
				assert(out->timed_out.native_value() && !out->stopped.native_value());
			} else if (action == 1) {
				assert(process::stop(group.handle).value().native_value());
				auto out = wait_for(group.handle);
				assert(out->stopped.native_value() && !out->timed_out.native_value());
			} else if (action == 2) {
				assert(process::close(group.handle).value().native_value());
			} else {
				group.handle = proc();
			}
			await_exit(group.descendant);
			assert(exited(group.leader));
		}
	}
	{
		// Already available exit status wins even when the first poll is late.
		ready_group group(true, 1);
		await_exit(group.leader); // Observe without reaping the runtime's child.
		std::this_thread::sleep_for(std::chrono::milliseconds(5));
		auto out = wait_for(group.handle);
		assert(out->exit_code.native_value() == 7);
		assert(!out->timed_out.native_value() && !out->stopped.native_value());
		await_exit(group.descendant);
	}
	{
		// A normally exiting leader leaves a descendant; completion must kill it.
		auto h = launch("sleep 30 & echo $!; exit 0");
		auto out = wait_for(h);
		pid_t descendant = std::stoi(out->stdout_text.native_value());
		await_exit(descendant);
		assert(out->exit_code.native_value() == 0);
	}
	{
		auto h = launch("sleep 0.1; printf survived");
		pid_t child = fork();
		assert(child >= 0);
		if (child == 0) {
			assert(process::stop(h).has_error().native_value());
			h = proc(); // Inherited destructor only closes inherited capture descriptors.
			_exit(0);
		}
		int status; assert(waitpid(child, &status, 0) == child && WEXITSTATUS(status) == 0);
		assert(wait_for(h)->stdout_text.native_value() == "survived");
	}
	{
		// Destructor cleanup is a real fallback for an unfinished child.
		auto h = launch("sleep 10");
	}
	{
		struct sigaction ignored {}, saved {};
		ignored.sa_handler = SIG_IGN; sigemptyset(&ignored.sa_mask);
		assert(sigaction(SIGCHLD, &ignored, &saved) == 0);
		vector_t<string_t> args;
		assert(process::start(string_t("/bin/true"), args, string_t(""), int_t<>(0)).has_error().native_value());
		assert(sigaction(SIGCHLD, &saved, nullptr) == 0);
	}
	{
		struct sigaction ignored {}, saved {};
		ignored.sa_handler = SIG_IGN; sigemptyset(&ignored.sa_mask);
		assert(sigaction(SIGPIPE, &ignored, &saved) == 0);
		auto h = launch("kill -PIPE $$; printf should-not-run");
		assert(sigaction(SIGPIPE, &saved, nullptr) == 0);
		assert(wait_for(h)->signal.native_value() == SIGPIPE);
	}
	{
		pid_t child = fork();
		assert(child >= 0);
		if (child == 0) {
			::close(0); ::close(1); ::close(2);
			auto h = launch("cat", "closed stdio");
			assert(wait_for(h)->stdout_text.native_value() == "closed stdio");
			_exit(0);
		}
		int status; assert(waitpid(child, &status, 0) == child && WIFEXITED(status) && WEXITSTATUS(status) == 0);
	}
	for (int i = 0; i < 20; ++i) { auto h = launch("sleep 10"); assert(process::close(h).value().native_value()); }
	assert(descriptors() == baseline);
	int status = 0;
	assert(waitpid(-1, &status, WNOHANG) == -1 && errno == ECHILD);
}
