#include "modules/filesystem/file_lock.hpp"

#include <cerrno>
#include <memory>
#include <string>
#include <system_error>
#include <utility>

#if defined(__linux__)
#include <fcntl.h>
#include <sys/file.h>
#include <sys/stat.h>
#include <unistd.h>
#endif

namespace scpp::fs {
namespace file_lock_detail {

[[nodiscard]] error_t failure(const char *operation, const char *reason) {
	return error_t(string_t(std::string(operation) + ": " + reason));
}

#if defined(__linux__)
[[nodiscard]] error_t os_failure(const char *operation, int code) {
	return error_t(string_t(std::string(operation) + ": " + std::error_code(code, std::generic_category()).message()));
}

int apply_lock(int descriptor, int operation) noexcept {
	int status;
	do {
		status = ::flock(descriptor, operation);
	} while (status == -1 && errno == EINTR);
	return status;
}
#endif

} // namespace file_lock_detail

file_lock::~file_lock() {
#if defined(__linux__)
	const int saved_errno = errno;
	if (descriptor_ >= 0) {
		// A forked child may close its copy, but must never unlock the parent's token.
		if (held_ && owner_pid_ == ::getpid()) {
			static_cast<void>(file_lock_detail::apply_lock(descriptor_, LOCK_UN));
		}
		static_cast<void>(::close(descriptor_));
	}
	errno = saved_errno;
#endif
}

result<bool_t> lock_try(shared_p<file_lock> &out, const string_t &path, const bool_t &shared) {
#if defined(__linux__)
	if (out.get() != nullptr && out->descriptor_ >= 0) {
		return file_lock_detail::failure("fs_lock_try", "output already owns or inherits a lock descriptor");
	}
	const auto &native_path = path.native_value();
	if (native_path.empty() || native_path.find('\0') != std::string::npos) {
		return file_lock_detail::failure("fs_lock_try", "path must be nonempty and contain no NUL bytes");
	}

	// Allocate the token before acquiring OS resources; every later failure has RAII cleanup.
	auto candidate = shared_p<file_lock>(std::shared_ptr<file_lock>(new file_lock()));
	const bool reader = shared.native_value();
	const int flags = O_CLOEXEC | O_NONBLOCK | (reader ? O_RDONLY : O_RDWR | O_CREAT);
	int descriptor;
	do {
		descriptor = ::open(native_path.c_str(), flags, 0666);
	} while (descriptor == -1 && errno == EINTR);
	if (descriptor == -1) {
		return file_lock_detail::os_failure("fs_lock_try/open", errno);
	}
	candidate->descriptor_ = descriptor;
	candidate->owner_pid_ = ::getpid();
	struct stat status {};
	if (::fstat(descriptor, &status) == -1) {
		return file_lock_detail::os_failure("fs_lock_try/stat", errno);
	}
	if (!S_ISREG(status.st_mode)) {
		return file_lock_detail::failure("fs_lock_try", "lock path must refer to a regular file");
	}
	if (file_lock_detail::apply_lock(descriptor, (reader ? LOCK_SH : LOCK_EX) | LOCK_NB) == -1) {
		const int code = errno;
		if (code == EWOULDBLOCK || code == EAGAIN) {
			return bool_t(false);
		}
		return file_lock_detail::os_failure("fs_lock_try/flock", code);
	}
	candidate->held_ = true;
	out = std::move(candidate);
	return bool_t(true);
#else
	static_cast<void>(out);
	static_cast<void>(path);
	static_cast<void>(shared);
	return file_lock_detail::failure("fs_lock_try", "file locks require Linux");
#endif
}

result<bool_t> lock_release(const shared_p<file_lock> &handle) {
#if defined(__linux__)
	if (handle.get() == nullptr || handle->descriptor_ < 0) {
		return bool_t(true);
	}
	if (handle->owner_pid_ != ::getpid()) {
		return file_lock_detail::failure("fs_lock_release", "inherited lock cannot be released by this process");
	}
	if (file_lock_detail::apply_lock(handle->descriptor_, LOCK_UN) == -1) {
		// Retain ownership for an explicit retry or destructor cleanup.
		return file_lock_detail::os_failure("fs_lock_release/unlock", errno);
	}
	handle->held_ = false;
	const int descriptor = std::exchange(handle->descriptor_, -1);
	// Linux frees the descriptor even on late close errors. Never retry a reused number.
	if (::close(descriptor) == -1) {
		return file_lock_detail::os_failure("fs_lock_release/close", errno);
	}
	return bool_t(true);
#else
	static_cast<void>(handle);
	return file_lock_detail::failure("fs_lock_release", "file locks require Linux");
#endif
}

result<shared_p<file_lock>> lock_transfer(const shared_p<file_lock> &handle) {
#if defined(__linux__)
	if (handle.get() == nullptr || handle->descriptor_ < 0 || !handle->held_) {
		return file_lock_detail::failure("fs_lock_transfer", "an acquired lock is required");
	}
	if (handle->owner_pid_ != ::getpid()) {
		return file_lock_detail::failure("fs_lock_transfer", "inherited lock cannot be transferred by this process");
	}
	auto next = shared_p<file_lock>(std::shared_ptr<file_lock>(new file_lock()));
	next->owner_pid_ = handle->owner_pid_;
	next->descriptor_ = std::exchange(handle->descriptor_, -1);
	next->held_ = std::exchange(handle->held_, false);
	return next;
#else
	static_cast<void>(handle);
	return file_lock_detail::failure("fs_lock_transfer", "file locks require Linux");
#endif
}

} // namespace scpp::fs
