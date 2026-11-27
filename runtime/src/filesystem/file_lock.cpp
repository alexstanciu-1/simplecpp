#include "filesystem_locks_provider.hpp"
#include <atomic>
#include <cerrno>
#include <string>
#include <system_error>
#include <utility>

#if defined(__linux__)
#include <fcntl.h>
#include <sys/file.h>
#include <sys/stat.h>
#include <unistd.h>
#endif

namespace scpp::detail {
#if defined(__linux__)
static int apply_lock(int fd, int operation) noexcept {
    int rc;
    do {
        rc = ::flock(fd, operation);
    } while (rc < 0 && errno == EINTR);
    return rc;
}
#endif

struct file_lock_handle_state {
    std::atomic<std::size_t> references{1};
#if defined(__linux__)
    int fd = -1;
    pid_t owner = 0;
    bool held = false;

    ~file_lock_handle_state() {
        if (fd < 0) return;
        const int saved = errno;
        if (held && owner == ::getpid()) apply_lock(fd, LOCK_UN);
        ::close(fd);
        errno = saved;
    }
#endif
};
}

namespace scpp::fs {
using detail::file_lock_handle_access;

static exception failure(const char* reason) {
    return exception{string{reason}};
}

file_lock_handle lock_empty() {
    return file_lock_handle_access::adopt(new detail::file_lock_handle_state);
}

#if defined(__linux__)
static exception system_failure(const char* operation, int code) {
    const auto message = std::string(operation) + ": " + std::generic_category().message(code);
    return exception{string{message.data(), static_cast<int64>(message.size())}};
}

result_bool_t lock_try(file_lock_handle& out, const string& path, bool_t shared) {
    const auto* current = file_lock_handle_access::get(out);
    if (current && current->fd >= 0) return result_bool_t{failure("lock output already holds a descriptor")};
    const std::string name(path.data(), path.size());
    if (name.empty() || name.find('\0') != name.npos) return result_bool_t{failure("invalid lock path")};
    auto candidate = lock_empty();
    auto* state = file_lock_handle_access::get(candidate);
    const int flags = O_CLOEXEC | O_NONBLOCK | (shared ? O_RDONLY : O_RDWR | O_CREAT);
    do {
        state->fd = ::open(name.c_str(), flags, 0666);
    } while (state->fd < 0 && errno == EINTR);
    if (state->fd < 0) return result_bool_t{system_failure("lock open", errno)};
    struct stat info{};
    if (::fstat(state->fd, &info) < 0) return result_bool_t{system_failure("lock stat", errno)};
    if (!S_ISREG(info.st_mode)) return result_bool_t{failure("lock requires a regular file")};
    state->owner = ::getpid();
    if (detail::apply_lock(state->fd, (shared ? LOCK_SH : LOCK_EX) | LOCK_NB) < 0) {
        const int code = errno;
        if (code == EWOULDBLOCK || code == EAGAIN) return result_bool_t{false};
        return result_bool_t{system_failure("lock acquisition", code)};
    }
    state->held = true;
    result_bool_t acquired{true};
    out = std::move(candidate);
    return acquired;
}

result_bool_t lock_release(const file_lock_handle& handle) {
    auto* state = file_lock_handle_access::get(handle);
    if (!state || state->fd < 0) return result_bool_t{true};
    if (state->owner != ::getpid()) return result_bool_t{failure("cannot release inherited lock")};
    if (detail::apply_lock(state->fd, LOCK_UN) < 0) return result_bool_t{system_failure("lock release", errno)};
    state->held = false;
    if (::close(std::exchange(state->fd, -1)) < 0) return result_bool_t{system_failure("lock close", errno)};
    return result_bool_t{true};
}

result_file_lock_handle lock_transfer(const file_lock_handle& handle) {
    auto* old = file_lock_handle_access::get(handle);
    if (!old || old->fd < 0) return result_file_lock_handle{failure("transfer requires a held lock")};
    if (old->owner != ::getpid()) return result_file_lock_handle{failure("cannot transfer inherited lock")};
    auto next = lock_empty();
    auto* state = file_lock_handle_access::get(next);
    // Construct the allocating result before detaching the old token.
    result_file_lock_handle result{std::move(next)};
    state->owner = old->owner;
    state->fd = std::exchange(old->fd, -1);
    state->held = std::exchange(old->held, false);
    return result;
}
#else
result_bool_t lock_try(file_lock_handle&, const string&, bool_t) {
    return result_bool_t{failure("file locks require Linux")};
}
result_bool_t lock_release(const file_lock_handle&) {
    return result_bool_t{failure("file locks require Linux")};
}
result_file_lock_handle lock_transfer(const file_lock_handle&) {
    return result_file_lock_handle{failure("file locks require Linux")};
}
#endif

result_bool_t lock_try(file_lock_handle& out, const string& path) {
    return lock_try(out, path, false);
}
}
#include "filesystem_locks_values.hpp"
