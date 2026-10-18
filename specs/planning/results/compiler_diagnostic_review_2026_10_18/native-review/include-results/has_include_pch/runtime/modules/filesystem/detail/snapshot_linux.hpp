#pragma once

#include <cerrno>
#include <cstdint>
#include <fcntl.h>
#include <sys/stat.h>
#include <unistd.h>
#include <system_error>
#include "modules/filesystem/detail/snapshot_reader.hpp"

namespace scpp::fs::snapshot_detail {
class linux_operations {
	const std::string &path_;
	int descriptor_ = -1;
public:
	using metadata = struct stat;
	explicit linux_operations(const std::string &path) : path_(path) {}
	linux_operations(const linux_operations &) = delete;
	linux_operations &operator=(const linux_operations &) = delete;
	~linux_operations() { close(); }
	bool path_status(metadata &status) {
		int rc;
		do { rc = ::lstat(path_.c_str(), &status); } while (rc == -1 && errno == EINTR);
		return rc == 0;
	}
	bool open() {
		// Never follow a swapped final symlink or block on a raced-in FIFO.
		do { descriptor_ = ::open(path_.c_str(), O_RDONLY | O_CLOEXEC | O_NOFOLLOW | O_NONBLOCK); }
		while (descriptor_ == -1 && errno == EINTR);
		return descriptor_ >= 0;
	}
	bool handle_status(metadata &status) {
		int rc;
		do { rc = ::fstat(descriptor_, &status); } while (rc == -1 && errno == EINTR);
		return rc == 0;
	}
	ssize_t read(char *buffer, std::size_t amount) {
		ssize_t count;
		do { count = ::read(descriptor_, buffer, amount); } while (count == -1 && errno == EINTR);
		return count;
	}
	void close() noexcept {
		if (descriptor_ >= 0) {
			const int descriptor = descriptor_;
			descriptor_ = -1;
			// Linux releases the descriptor even on close error. Never retry.
			static_cast<void>(::close(descriptor));
		}
	}
	static bool regular(const metadata &s) { return S_ISREG(s.st_mode); }
	static std::int64_t mtime(const metadata &s) { return s.st_mtime; }
	static std::uint64_t size(const metadata &s) { return static_cast<std::uint64_t>(s.st_size); }
	static bool same_identity(const metadata &a, const metadata &b) { return a.st_dev == b.st_dev && a.st_ino == b.st_ino; }
	static error_t error(const char *operation) {
		const int code = errno;
		return error_t(string_t(std::string("fs_read_snapshot: ") + operation + ": " + std::error_code(code, std::generic_category()).message()));
	}
};
} // namespace scpp::fs::snapshot_detail
