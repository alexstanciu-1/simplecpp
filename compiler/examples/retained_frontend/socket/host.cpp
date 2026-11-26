#include "frontend_host.hpp"
#include <chrono>
#include <cerrno>
#include <cstring>
#include <poll.h>
#include <stdexcept>
#include <string>
#include <system_error>
#include <sys/socket.h>
#include <sys/stat.h>
#include <sys/un.h>
#include <unistd.h>

namespace {
constexpr char path[] = ".scpp/compiler.sock";
int listener = -1;
int client = -1;
struct stat bound{};
using Clock = std::chrono::steady_clock;

void fail(const char* what) { throw std::system_error(errno, std::generic_category(), what); }

void close_fd(int& fd) {
    if (fd >= 0) ::close(fd);
    fd = -1;
}

bool ready(int fd, short events, Clock::time_point until) {
    for (;;) {
        auto ms = std::chrono::ceil<std::chrono::milliseconds>(until - Clock::now()).count();
        if (ms <= 0) return false;
        pollfd item{fd, events, 0};
        int result = ::poll(&item, 1, static_cast<int>(ms));
        if (result >= 0) return result > 0;
        if (errno != EINTR) fail("poll");
    }
}
}

namespace App {
void host_directory_() {
    ::umask(0077);
    if (::mkdir(".scpp", 0700) < 0 && errno != EEXIST) fail("mkdir");
    struct stat dir{};
    if (::lstat(".scpp", &dir) < 0) fail("lstat directory");
    if (!S_ISDIR(dir.st_mode) || dir.st_uid != ::geteuid() || (dir.st_mode & 0077))
        throw std::runtime_error("Expected private application service directory");
}

void host_listen_() {
    struct stat old{};
    if (::lstat(path, &old) == 0) {
        if (!S_ISSOCK(old.st_mode)) throw std::runtime_error("Occupied frontend socket path");
        if (::unlink(path) < 0) fail("unlink stale socket");
    } else if (errno != ENOENT) fail("lstat endpoint");
    listener = ::socket(AF_UNIX, SOCK_STREAM | SOCK_CLOEXEC, 0);
    if (listener < 0) fail("socket");
    sockaddr_un address{};
    address.sun_family = AF_UNIX;
    std::memcpy(address.sun_path, path, sizeof(path));
    if (::bind(listener, reinterpret_cast<sockaddr*>(&address), sizeof(address)) < 0) fail("bind");
    if (::lstat(path, &bound) < 0) fail("lstat bound socket");
    if (::chmod(path, 0600) < 0 || ::listen(listener, 16) < 0) fail("enable listener");
}

scpp::string host_request_() {
    const auto idle = Clock::now() + std::chrono::hours(1);
    while (ready(listener, POLLIN, idle)) {
        client = ::accept4(listener, nullptr, nullptr, SOCK_CLOEXEC | SOCK_NONBLOCK);
        if (client < 0) {
            if (errno == EINTR || errno == ECONNABORTED) continue;
            fail("accept");
        }
        const auto deadline = Clock::now() + std::chrono::seconds(5);
        std::string request;
        char bytes[256];
        while (request.size() < 4096 && ready(client, POLLIN, deadline)) {
            auto count = ::recv(client, bytes, sizeof(bytes), 0);
            if (count < 0 && (errno == EINTR || errno == EAGAIN)) continue;
            if (count <= 0) break;
            request.append(bytes, static_cast<size_t>(count));
            auto end = request.find('\n');
            if (end < 4096) return scpp::string(request.data(), static_cast<scpp::int64>(end + 1));
        }
        constexpr char incomplete[] = "{\"error\":\"Incomplete service request\"}\n";
        host_reply_(scpp::string(incomplete, sizeof(incomplete) - 1));
    }
    return {};
}

void host_reply_(scpp::string response) {
    const auto deadline = Clock::now() + std::chrono::seconds(5);
    scpp::int64 offset = 0;
    while (offset < response.size() && ready(client, POLLOUT, deadline)) {
        auto written = ::send(client, response.data() + offset, response.size() - offset, MSG_NOSIGNAL);
        if (written < 0 && (errno == EINTR || errno == EAGAIN)) continue;
        if (written <= 0) break;
        offset += written;
    }
    close_fd(client);
}

void host_close_() {
    close_fd(client);
    close_fd(listener);
    struct stat now{};
    if (::lstat(path, &now) == 0 && now.st_dev == bound.st_dev && now.st_ino == bound.st_ino
        && S_ISSOCK(now.st_mode) && ::unlink(path) < 0) fail("unlink socket");
}
}
