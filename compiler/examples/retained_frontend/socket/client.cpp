// Linux native client operations. The calling frontend validates the three protocol commands.
#include <cerrno>
#include <chrono>
#include <cstring>
#include <fcntl.h>
#include <poll.h>
#include <stdexcept>
#include <string>
#include <system_error>
#include <sys/socket.h>
#include <sys/stat.h>
#include <sys/un.h>
#include <sys/wait.h>
#include <unistd.h>

namespace {
constexpr char endpoint[] = ".scpp/compiler.sock";
struct Descriptor {
    int fd;
    ~Descriptor() { if (fd >= 0) ::close(fd); }
};
void fail(const char* operation) { throw std::system_error(errno, std::generic_category(), operation); }

void check_path(const char* name, mode_t type) {
    struct stat st{};
    if (::lstat(name, &st) < 0) {
        if (errno == ENOENT) return;
        fail(name);
    }
    if ((st.st_mode & S_IFMT) != type || st.st_uid != ::geteuid())
        throw std::runtime_error(std::string("Unexpected service path: ") + name);
}

int connect_once() {
    const int fd = ::socket(AF_UNIX, SOCK_STREAM | SOCK_CLOEXEC | SOCK_NONBLOCK, 0);
    if (fd < 0) fail("socket");
    sockaddr_un addr{};
    addr.sun_family = AF_UNIX;
    std::memcpy(addr.sun_path, endpoint, sizeof(endpoint));
    if (::connect(fd, reinterpret_cast<sockaddr*>(&addr), sizeof(addr)) == 0) return fd;
    const int err = errno;
    ::close(fd);
    if (err == ECONNREFUSED || err == ENOENT || err == EAGAIN) return -1;
    throw std::system_error(err, std::generic_category(), "connect");
}

void launch() {
    // Reap the intermediate child; the detached daemon survives the client.
    pid_t child = ::fork();
    if (child < 0) fail("fork");
    if (!child) {
        if (::setsid() < 0) ::_exit(1);
        pid_t daemon = ::fork();
        if (daemon < 0) ::_exit(1);
        if (daemon) ::_exit(0);
        int in = ::open("/dev/null", O_RDONLY);
        int log = ::open(".scpp/compiler.log", O_WRONLY | O_APPEND | O_CREAT | O_NOFOLLOW | O_CLOEXEC | O_NONBLOCK, 0600);
        if (in < 0 || log < 0 || ::dup2(in, 0) < 0 || ::dup2(log, 1) < 0 || ::dup2(log, 2) < 0) ::_exit(1);
        ::close(in);
        ::close(log);
        ::execl("/proc/self/exe", "frontend-service", static_cast<char*>(nullptr));
        ::_exit(1);
    }
    int status = 0;
    while (::waitpid(child, &status, 0) < 0) if (errno != EINTR) fail("waitpid");
    if (!WIFEXITED(status) || WEXITSTATUS(status)) throw std::runtime_error("Cannot launch service");
}
}

namespace App {
scpp::int64 host_pid_() { return ::getpid(); }

scpp::string host_exchange_(scpp::string app, scpp::string command) {
    const std::string root(app.data(), app.size()), cmd(command.data(), command.size());
    if (root.find('\0') != std::string::npos || ::chdir(root.c_str()) < 0)
        throw std::runtime_error("Frontend service requires an application directory");
    check_path(".scpp", S_IFDIR);
    check_path(endpoint, S_IFSOCK);
    check_path(".scpp/compiler.lock", S_IFREG);
    check_path(".scpp/compiler.log", S_IFREG);
    Descriptor server{connect_once()};
    if (server.fd < 0) {
        if (cmd != "frontend") return scpp::string("{\"running\":false}\n");
        host_directory_();
        launch();
        auto deadline = std::chrono::steady_clock::now() + std::chrono::seconds(5);
        while ((server.fd = connect_once()) < 0) {
            if (std::chrono::steady_clock::now() >= deadline) throw std::runtime_error("Frontend service did not start");
            ::usleep(10000);
        }
    }
    std::string request = "{\"command\":\"" + cmd + "\"}\n";
    const auto deadline = std::chrono::steady_clock::now() + std::chrono::hours(1);
    std::string response;
    char bytes[4096];
    size_t sent = 0;
    while (std::chrono::steady_clock::now() < deadline) {
        const bool writing = sent < request.size();
        pollfd p{server.fd, static_cast<short>(writing ? POLLOUT : POLLIN), 0};
        auto ms = std::chrono::ceil<std::chrono::milliseconds>(deadline - std::chrono::steady_clock::now()).count();
        if (ms <= 0) break;
        int n = ::poll(&p, 1, static_cast<int>(ms));
        if (n < 0 && errno == EINTR) continue;
        if (n < 0) fail("poll");
        if (!n) break;
        ssize_t size = writing ? ::send(server.fd, request.data() + sent, request.size()-sent, MSG_NOSIGNAL)
                               : ::recv(server.fd, bytes, sizeof(bytes), 0);
        if (size < 0 && (errno == EINTR || errno == EAGAIN)) continue;
        if (size <= 0) throw std::runtime_error("Frontend service disconnected");
        if (writing) { sent += size; continue; }
        response.append(bytes, size);
        auto end = response.find('\n');
        if (end < 1048575) return scpp::string(response.data(), static_cast<scpp::int64>(end + 1));
        if (response.size() >= 1048575) throw std::runtime_error("Oversized service response");
    }
    throw std::runtime_error("Frontend service timed out");
}
}
