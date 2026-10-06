#include "modules/filesystem/file_lock.hpp"

#include <fcntl.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include <unistd.h>

#include <filesystem>
#include <fstream>
#include <functional>
#include <iostream>
#include <stdexcept>
#include <string>

using namespace scpp;
using lock_handle = shared_p<file_lock_handle>;

static void check(bool condition, const char *message) {
	if (!condition) throw std::runtime_error(message);
}

static bool acquired(const result<bool_t> &value) {
	check(value.has_value().native_value(), "expected success, got error");
	return value.value().native_value();
}

static void wait_child(pid_t pid) {
	int status = 0;
	check(::waitpid(pid, &status, 0) == pid, "waitpid failed");
	check(WIFEXITED(status) && WEXITSTATUS(status) == 0, "child proof failed");
}

static void in_child(const std::function<void()> &body) {
	const auto pid = ::fork();
	check(pid >= 0, "fork failed");
	if (pid == 0) {
		try { body(); ::_exit(0); }
		catch (const std::exception &e) { std::cerr << e.what() << '\n'; ::_exit(1); }
	}
	wait_child(pid);
}

static void probe(const string_t &path, bool shared, bool expected) {
	lock_handle other;
	check(acquired(fs::lock_try(other, path, bool_t(shared))) == expected, "unexpected contention state");
	check(acquired(fs::lock_release(other)), "release failed");
}

static int descriptor_for(const std::string &path) {
	struct stat target {};
	check(::stat(path.c_str(), &target) == 0, "stat fixture failed");
	for (const auto &entry : std::filesystem::directory_iterator("/proc/self/fd")) {
		const int fd = std::stoi(entry.path().filename().string());
		struct stat candidate {};
		if (::fstat(fd, &candidate) == 0 && candidate.st_dev == target.st_dev && candidate.st_ino == target.st_ino) return fd;
	}
	return -1;
}

static void php_probe(const std::string &path, bool shared, bool expected) {
	in_child([&] {
		const char *code = "$f=fopen($argv[1], 'r'); $blocked=0; $ok=flock($f, ($argv[2]==='1'?LOCK_SH:LOCK_EX)|LOCK_NB, $blocked); exit(($ok===($argv[3]==='1') && ($ok || $blocked===1))?0:1);";
		::execlp("php", "php", "-r", code, path.c_str(), shared ? "1" : "0", expected ? "1" : "0", nullptr);
		throw std::runtime_error("PHP exec failed");
	});
}

int main(int argc, char **argv) {
	try {
		if (argc == 3 && std::string(argv[1]) == "--exec-check") {
			check(descriptor_for(argv[2]) == -1, "lock descriptor survived exec");
			probe(string_t(argv[2]), false, false);
			return 0;
		}
		char directory[] = "/tmp/scpp-file-lock-XXXXXX";
		check(::mkdtemp(directory) != nullptr, "mkdtemp failed");
		struct cleanup {
			std::string path;
			~cleanup() { std::filesystem::remove_all(path); }
		} cleanup_directory{directory};
		const std::string name = std::string(directory) + "/owner.lock";
		const string_t path(name);
		{ std::ofstream file(name); file << "persistent contents"; }
		struct stat before {};
		check(::stat(name.c_str(), &before) == 0, "stat failed");
		lock_handle owner;
		check(acquired(fs::lock_try(owner, path)), "writer acquire failed");
		auto alias = owner;
		check(fs::lock_try(owner, path).has_error().native_value(), "occupied output accepted");
		in_child([&] {
			check(fs::lock_release(owner).has_error().native_value(), "inherited release accepted");
			check(fs::lock_transfer(owner).has_error().native_value(), "inherited transfer accepted");
			owner = null; alias = null; // Child destruction must not unlock the parent.
			probe(path, false, false);
			probe(path, true, false);
		});
		php_probe(name, false, false);
		php_probe(name, true, false);
		in_child([&] {
			::execl(argv[0], argv[0], "--exec-check", name.c_str(), nullptr);
			throw std::runtime_error("self exec failed");
		});

		auto moved = fs::lock_transfer(owner);
		check(moved.has_value().native_value(), "transfer failed");
		lock_handle next = moved.value();
		check(acquired(fs::lock_release(alias)), "old alias release should be harmless");
		check(fs::lock_transfer(alias).has_error().native_value(), "old alias transferred twice");
		in_child([&] { probe(path, false, false); });
		check(acquired(fs::lock_release(next)), "transferred release failed");
		check(acquired(fs::lock_release(next)), "double release failed");
		in_child([&] { probe(path, false, true); });

		check(::chmod(name.c_str(), 0444) == 0, "chmod failed");
		check(acquired(fs::lock_try(owner, path, bool_t(true))), "reader acquire failed");
		const int reader_fd = descriptor_for(name);
		check(reader_fd >= 0 && (::fcntl(reader_fd, F_GETFL) & O_ACCMODE) == O_RDONLY, "reader did not open read-only");
		check((::fcntl(reader_fd, F_GETFD) & FD_CLOEXEC) != 0, "missing close-on-exec");
		in_child([&] { probe(path, true, true); });
		php_probe(name, true, true);
		php_probe(name, false, false);
		check(::chmod(name.c_str(), 0644) == 0, "chmod failed");
		in_child([&] { probe(path, false, false); });
		check(acquired(fs::lock_release(owner)), "reader release failed");

		// The parent must unlock even while a pre-exec child retains its descriptor.
		check(acquired(fs::lock_try(owner, path)), "reacquire failed");
		int gate[2];
		check(::pipe(gate) == 0, "pipe failed");
		const auto pid = ::fork();
		check(pid >= 0, "fork failed");
		if (pid == 0) {
			::close(gate[1]);
			char value;
			if (::read(gate[0], &value, 1) != 1) ::_exit(1);
			try { probe(path, false, true); ::_exit(0); } catch (...) { ::_exit(1); }
		}
		::close(gate[0]);
		check(acquired(fs::lock_release(owner)), "parent unlock failed");
		check(::write(gate[1], "x", 1) == 1, "gate failed");
		::close(gate[1]);
		wait_child(pid);

		// Last-alias destruction releases; no explicit release call.
		{ lock_handle scoped; check(acquired(fs::lock_try(scoped, path)), "scoped acquire failed"); }
		in_child([&] { probe(path, false, true); });
		lock_handle empty;
		check(acquired(fs::lock_release(empty)), "empty release failed");
		check(fs::lock_transfer(empty).has_error().native_value(), "empty transfer accepted");
		check(fs::lock_try(empty, string_t("")).has_error().native_value(), "empty path accepted");
		check(fs::lock_try(empty, string_t(std::string("a\0b", 3))).has_error().native_value(), "NUL path accepted");
		check(fs::lock_try(empty, string_t(directory)).has_error().native_value(), "directory accepted");
		const string_t missing(std::string(directory) + "/missing.lock");
		check(fs::lock_try(empty, missing, bool_t(true)).has_error().native_value(), "missing reader accepted");
		check(!std::filesystem::exists(missing.native_value()), "reader created a file");
		check(acquired(fs::lock_try(empty, missing)), "exclusive create failed");
		check(acquired(fs::lock_release(empty)), "created release failed");
		const auto old_empty = empty.get();
		check(fs::lock_try(empty, string_t(std::string(directory) + "/absent/file")).has_error().native_value(), "missing parent accepted");
		check(empty.get() == old_empty, "failure changed output");
		check(acquired(fs::lock_try(owner, path)), "writer reacquire failed");
		in_child([&] {
			check(!acquired(fs::lock_try(empty, path)), "expected contention");
			check(empty.get() == old_empty, "contention changed output");
		});
		check(acquired(fs::lock_release(owner)), "final release failed");
		struct stat after {};
		check(::stat(name.c_str(), &after) == 0 && before.st_ino == after.st_ino && before.st_dev == after.st_dev, "lock identity changed");
		std::ifstream file(name);
		std::string contents((std::istreambuf_iterator<char>(file)), {});
		check(contents == "persistent contents", "lock file truncated");
		const std::string fifo = std::string(directory) + "/fifo";
		check(::mkfifo(fifo.c_str(), 0600) == 0, "mkfifo failed");
		check(fs::lock_try(empty, string_t(fifo), bool_t(true)).has_error().native_value(), "FIFO accepted");
		const auto fd_count = [] {
			return std::distance(std::filesystem::directory_iterator("/proc/self/fd"), std::filesystem::directory_iterator());
		};
		const auto baseline = fd_count();
		for (int index = 0; index < 100; ++index) {
			lock_handle scoped;
			check(acquired(fs::lock_try(scoped, path)), "repeated acquire failed");
			lock_handle contended;
			check(!acquired(fs::lock_try(contended, path)), "repeated contention failed");
			check(fs::lock_try(contended, string_t(fifo), bool_t(true)).has_error().native_value(), "repeated error failed");
		}
		check(fd_count() == baseline, "descriptor leak across success/contention/failure");
		std::cout << "PASS: file locks (native/PHP processes, ownership, inheritance)\n";
		return 0;
	} catch (const std::exception &e) {
		std::cerr << e.what() << '\n';
		return 1;
	}
}
