#include "process_provider.hpp"
#include <atomic>
#include <memory>
#include <utility>
#include <vector>
#include <algorithm>
#include <cerrno>
#include <chrono>
#include <limits>
#include <string>
#include <system_error>

#if defined(__linux__)
#include <fcntl.h>
#include <signal.h>
#include <pthread.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include <unistd.h>
extern char **environ;
#endif

namespace scpp::process {
namespace process_detail {
exception failure(const char *operation, const std::string &reason) {
	auto message = std::string("process_") + operation + ": " + reason;
	return exception{string{message.data(), static_cast<int64>(message.size())}};
}
exception os_failure(const char *operation, int code) {
	return failure(operation, std::error_code(code, std::generic_category()).message());
}
#if defined(__linux__)
// Never retry close: the descriptor number may already have been reused.
struct descriptor final {
	int value = -1;
	~descriptor() { reset(); }
	void reset() noexcept { if (value >= 0) { ::close(value); value = -1; } }
};
int private_file(descriptor &fd) {
	char path[] = "/tmp/scpp-process-XXXXXX";
	int raw = ::mkostemp(path, O_CLOEXEC);
	if (raw < 0) { return errno; }
	if (::unlink(path) != 0) { int code = errno; ::close(raw); return code; }
	// Keep every internal descriptor above stderr, even in a host with closed stdio.
	if (raw < 3) {
		int high = ::fcntl(raw, F_DUPFD_CLOEXEC, 3);
		int code = errno;
		::close(raw);
		if (high < 0) { return code; }
		raw = high;
	}
	fd.value = raw;
	return 0;
}
int write_all(int fd, const char *data, std::size_t size) {
	while (size > 0) {
		ssize_t count = ::write(fd, data, std::min(size, std::size_t(1024 * 1024)));
		if (count < 0 && errno == EINTR) { continue; }
		if (count <= 0) { return count < 0 ? errno : EIO; }
		data += count; size -= static_cast<std::size_t>(count);
	}
	return 0;
}
int read_capture(int fd, string &out) {
	struct stat status {};
	if (::fstat(fd, &status) != 0) { return errno; }
	if (status.st_size < 0 || static_cast<std::uintmax_t>(status.st_size) > std::numeric_limits<std::size_t>::max()) { return EFBIG; }
	std::string bytes(static_cast<std::size_t>(status.st_size), '\0');
	std::size_t offset = 0;
	while (offset < bytes.size()) {
		ssize_t count = ::pread(fd, bytes.data() + offset, std::min(bytes.size() - offset, std::size_t(1024 * 1024)), static_cast<off_t>(offset));
		if (count < 0 && errno == EINTR) { continue; }
		if (count <= 0) { return count < 0 ? errno : EIO; }
		offset += static_cast<std::size_t>(count);
	}
	out = string{bytes.data(), static_cast<int64>(bytes.size())};
	return 0;
}
#endif
}

}
namespace scpp::detail {
struct process_handle_state final {
	std::atomic<size_t> references{1};
#if defined(__linux__)
	process::process_detail::descriptor input, out, err;
	pid_t child = -1;
	pid_t owner = ::getpid();
	std::chrono::steady_clock::time_point began;
	std::int64_t timeout = 0;
	bool signalled = false;
	bool complete = false;
	bool closed = false;
	std::unique_ptr<process_output> capture;
	process_output snapshot;

	// Called only while the direct child remains ours and unreaped. No external
	// SIGCHLD reaper may compete with this module for its children.
	int kill_group() noexcept {
		if (child < 0) { return 0; }
		if (::kill(-child, SIGKILL) != 0 && errno != ESRCH) { return errno; }
		signalled = true;
		return 0;
	}
	int observe(siginfo_t &info) noexcept {
		int status;
		do { status = ::waitid(P_PID, static_cast<id_t>(child), &info, WEXITED | WNOHANG | WNOWAIT); } while (status < 0 && errno == EINTR);
		if (status < 0) {
			int code = errno;
			if (code == ECHILD) { child = -1; } // Ownership lost: never signal this number again.
			return code;
		}
		return 0;
	}
	int finish(bool blocking) noexcept {
		int status = 0;
		pid_t waited;
		do { waited = ::waitpid(child, &status, blocking ? 0 : WNOHANG); } while (waited < 0 && errno == EINTR);
		if (waited < 0) { int code = errno; if (code == ECHILD) child = -1; return code; }
		if (waited == 0) { return 0; }
		child = -1; complete = true;
		snapshot.exit_code = int64(WIFEXITED(status) ? WEXITSTATUS(status) : -1);
		snapshot.signal = int64(WIFSIGNALED(status) ? WTERMSIG(status) : 0);
		return 0;
	}
	int cleanup() noexcept {
		if (owner != ::getpid()) { input.reset(); out.reset(); err.reset(); capture.reset(); closed = true; return 0; }
		if (child >= 0) {
			siginfo_t info {};
			int code = observe(info);
			if (code != 0 && code != ECHILD) { return code; }
			if (child >= 0) {
				if ((code = kill_group()) != 0) { return code; }
				if ((code = finish(true)) != 0 && code != ECHILD) { return code; }
			}
		}
		input.reset(); out.reset(); err.reset(); capture.reset(); closed = true;
		return 0;
	}
	~process_handle_state() { int saved = errno; static_cast<void>(cleanup()); errno = saved; }
#endif
};
}
namespace scpp::process {

result_process_handle start(const string &executable, const vector_string &args,
	const string &input, int64 timeout_ms, const string &cwd) {
#if defined(__linux__)
	using namespace process_detail;
	const std::string path(executable.data(), executable.size());
	const std::string directory(cwd.data(), cwd.size());
	if (path.empty() || path[0] != '/' || path.find('\0') != std::string::npos ||
		(!directory.empty() && (directory[0] != '/' || directory.find('\0') != std::string::npos)) || timeout_ms < 0) {
		return result_process_handle{failure("start", "absolute executable/cwd paths without NUL and a nonnegative timeout are required")};
	}
	struct sigaction child_action {};
	if (::sigaction(SIGCHLD, nullptr, &child_action) != 0) { return result_process_handle{os_failure("start/sigaction", errno)}; }
	if (child_action.sa_handler != SIG_DFL || (child_action.sa_flags & SA_NOCLDWAIT) != 0) {
		return result_process_handle{failure("start", "requires default SIGCHLD disposition and exclusive ownership of child waits")};
	}
	std::vector<std::string> arguments;
	arguments.reserve(args.size());
	for (int64 i = 0; i < args.size(); ++i) {
		auto argument = args.at(i);
		auto& bytes = arguments.emplace_back(argument.data(), argument.size());
		if (bytes.find('\0') != bytes.npos) return result_process_handle{failure("start", "argument contains NUL")};
	}
	std::vector<char *> argv{const_cast<char*>(path.c_str())};
	for (auto& argument : arguments) argv.push_back(argument.data());
	argv.push_back(nullptr);
	auto value = detail::process_handle_access::adopt(new detail::process_handle_state);
	auto &s = *detail::process_handle_access::get(value);
	int code;
	if ((code = private_file(s.input)) || (code = private_file(s.out)) || (code = private_file(s.err))) { return result_process_handle{os_failure("start/tempfile", code)}; }
	if ((code = write_all(s.input.value, input.data(), input.size()))) { return result_process_handle{os_failure("start/stdin", code)}; }
	if (::lseek(s.input.value, 0, SEEK_SET) < 0) { return result_process_handle{os_failure("start/rewind", errno)}; }
	int pipefd[2];
	if (::pipe2(pipefd, O_CLOEXEC) != 0) { return result_process_handle{os_failure("start/pipe", errno)}; }
	descriptor reader, writer;
	reader.value = pipefd[0]; writer.value = pipefd[1];
	// Standard descriptors might be closed; pipe endpoints must not be clobbered by dup2.
	for (descriptor *fd : {&reader, &writer}) {
		if (fd->value < 3) {
			int high = ::fcntl(fd->value, F_DUPFD_CLOEXEC, 3);
			if (high < 0) { return result_process_handle{os_failure("start/pipe-duplicate", errno)}; }
			fd->reset(); fd->value = high;
		}
	}
	const char *exec_path = path.c_str();
	const char *exec_cwd = directory.empty() ? nullptr : directory.c_str();
	char **exec_argv = argv.data();
	char **exec_environment = environ;
	const int in_fd = s.input.value, out_fd = s.out.value, err_fd = s.err.value, report_fd = writer.value;
	sigset_t empty_mask; ::sigemptyset(&empty_mask);
	struct sigaction default_action {}; default_action.sa_handler = SIG_DFL; ::sigemptyset(&default_action.sa_mask);
	sigset_t all_signals, previous_mask; ::sigfillset(&all_signals);
	code = ::pthread_sigmask(SIG_SETMASK, &all_signals, &previous_mask);
	if (code != 0) { return result_process_handle{os_failure("start/signal-mask", code)}; }
	pid_t child = ::fork();
	const int fork_error = errno;
	if (child != 0) { ::pthread_sigmask(SIG_SETMASK, &previous_mask, nullptr); }
	if (child < 0) { return result_process_handle{os_failure("start/fork", fork_error)}; }
	if (child == 0) {
		// Async-signal-safe operations only: no allocation or C++ cleanup after fork.
		int launch_error = 0;
		for (int signal_number = 1; signal_number < NSIG; ++signal_number) {
			if (signal_number == SIGKILL || signal_number == SIGSTOP) continue;
			if (::sigaction(signal_number, &default_action, nullptr) != 0 && errno != EINVAL) { launch_error = errno; break; }
		}
		if (launch_error != 0) {
			// Keep the first signal setup error for the launch report below.
		} else if (::setpgid(0, 0) != 0 || (exec_cwd && ::chdir(exec_cwd) != 0) ||
			::dup2(in_fd, 0) < 0 || ::dup2(out_fd, 1) < 0 || ::dup2(err_fd, 2) < 0 ||
			::sigprocmask(SIG_SETMASK, &empty_mask, nullptr) != 0) {
			launch_error = errno;
		} else {
			::execve(exec_path, exec_argv, exec_environment);
			launch_error = errno;
		}
		const char *bytes = reinterpret_cast<const char *>(&launch_error);
		std::size_t remaining = sizeof(launch_error);
		while (remaining != 0) {
			ssize_t count = ::write(report_fd, bytes, remaining);
			if (count < 0 && errno == EINTR) continue;
			if (count <= 0) break;
			bytes += count; remaining -= static_cast<std::size_t>(count);
		}
		::_exit(127);
	}
	writer.reset();
	// EOF means exec closed the reporting descriptor; an errno payload means setup failed.
	int launch_error = 0;
	std::size_t received = 0;
	while (received < sizeof(launch_error)) {
		ssize_t count = ::read(reader.value, reinterpret_cast<char *>(&launch_error) + received, sizeof(launch_error) - received);
		if (count < 0 && errno == EINTR) continue;
		if (count < 0) { launch_error = errno; received = sizeof(launch_error); break; }
		if (count == 0) break;
		received += static_cast<std::size_t>(count);
	}
	if (received != 0) {
		// Launch failed before executing user code. Do not assume setpgid succeeded.
		::kill(child, SIGKILL);
		while (::waitpid(child, nullptr, 0) < 0 && errno == EINTR) {}
		return result_process_handle{os_failure("start/exec", received == sizeof(launch_error) ? launch_error : EIO)};
	}
	s.child = child;
	s.timeout = timeout_ms;
	s.began = std::chrono::steady_clock::now();
	s.input.reset();
	return result_process_handle{value};
#else
	return result_process_handle{process_detail::failure("start", "managed processes require Linux")};
#endif
}

result_bool_t poll(const process_handle &value) {
#if defined(__linux__)
	using namespace process_detail;
	if (detail::process_handle_access::get(value) == nullptr || detail::process_handle_access::get(value)->closed || detail::process_handle_access::get(value)->owner != ::getpid()) { return result_bool_t{failure("poll", "invalid, closed or inherited handle")}; }
	auto &s = *detail::process_handle_access::get(value);
	if (s.complete) return result_bool_t{bool_t(true)};
	if (s.child < 0) return result_bool_t{failure("poll", "child wait ownership was lost")};
	siginfo_t info {};
	int code = s.observe(info);
	if (code) return result_bool_t{os_failure("poll/waitid", code)};
	if (info.si_pid != 0) {
		if ((code = s.kill_group()) || (code = s.finish(false))) return result_bool_t{os_failure("poll/cleanup", code)};
		return result_bool_t{bool_t(s.complete)};
	}
	if (!s.signalled && s.timeout > 0 && std::chrono::duration_cast<std::chrono::milliseconds>(std::chrono::steady_clock::now() - s.began).count() >= s.timeout) {
		if ((code = s.kill_group())) return result_bool_t{os_failure("poll/timeout", code)};
		s.snapshot.timed_out = bool_t(true);
	}
	return result_bool_t{bool_t(false)};
#else
	return result_bool_t{process_detail::failure("poll", "managed processes require Linux")};
#endif
}

result_bool_t stop(const process_handle &value) {
#if defined(__linux__)
	auto status = poll(value); // Observe an already available exit before choosing a cause.
	if (!status.has_value() || status.value()) return status;
	auto &s = *detail::process_handle_access::get(value);
	if (!s.signalled) {
		int code = s.kill_group();
		if (code) return result_bool_t{process_detail::os_failure("stop/kill", code)};
		s.snapshot.stopped = bool_t(true);
	}
	return result_bool_t{bool_t(true)};
#else
	return result_bool_t{process_detail::failure("stop", "managed processes require Linux")};
#endif
}

result_process_output collect(const process_handle &value) {
#if defined(__linux__)
	using namespace process_detail;
	if (detail::process_handle_access::get(value) == nullptr || detail::process_handle_access::get(value)->closed || !detail::process_handle_access::get(value)->complete || detail::process_handle_access::get(value)->owner != ::getpid()) { return result_process_output{failure("result", "requires a complete, open, locally owned handle")}; }
	auto &s = *detail::process_handle_access::get(value);
	if (!s.capture) {
		process_output snapshot = s.snapshot;
		int code;
		if ((code = read_capture(s.out.value, snapshot.stdout_text)) || (code = read_capture(s.err.value, snapshot.stderr_text))) return result_process_output{os_failure("result/read", code)};
		s.capture = std::make_unique<process_output>(std::move(snapshot));
	}
	return result_process_output{*s.capture};
#else
	return result_process_output{process_detail::failure("result", "managed processes require Linux")};
#endif
}

result_bool_t close(const process_handle &value) {
#if defined(__linux__)
	if (detail::process_handle_access::get(value) == nullptr) return result_bool_t{bool_t(true)};
	if (detail::process_handle_access::get(value)->owner != ::getpid()) return result_bool_t{process_detail::failure("close", "inherited handle is not owned by this process")};
	int code = detail::process_handle_access::get(value)->cleanup();
	if (code) return result_bool_t{process_detail::os_failure("close", code)};
	return result_bool_t{bool_t(true)};
#else
	return result_bool_t{process_detail::failure("close", "managed processes require Linux")};
#endif
}

result_process_handle start(const string& executable, const vector_string& args, const string& input, int64 timeout_ms) {
	return start(executable, args, input, timeout_ms, string{});
}
}
#include "process_values.hpp"
