// Adapted from the v0.1 Unix FastCGI host at d2f63e37e (build_request and write_response).
#include "fcgi_provider.hpp"
#include "scpp/value_error.hpp"
#include <fcgiapp.h>
#include <algorithm>
#include <atomic>
#include <cerrno>
#include <climits>
#include <cstring>
#include <poll.h>
#include <stdexcept>
#include <string>
#include <string_view>
#include <sys/socket.h>
#include <sys/stat.h>
#include <sys/un.h>
#include <system_error>
#include <unistd.h>
#include <utility>

namespace scpp::detail {
struct fcgi_listener_state {
    std::atomic<size_t> references{1};
    int fd = -1;
    std::string path;
    struct stat identity{};
    size_t limit = 0;
    bool busy = false;
    void close_socket() noexcept {
        if (fd < 0) return;
        ::close(std::exchange(fd, -1));
        struct stat current{};
        if (::lstat(path.c_str(), &current) == 0 && current.st_dev == identity.st_dev &&
            current.st_ino == identity.st_ino && S_ISSOCK(current.st_mode)) ::unlink(path.c_str());
    }
    ~fcgi_listener_state() { close_socket(); }
};

struct fcgi_exchange_state {
    std::atomic<size_t> references{1};
    fcgi_listener owner;
    FCGX_Request raw{};
    bool active = false;
    fcgi_request snapshot;
    std::string headers;
    bool content_type = false;
    void finish() noexcept {
        FCGX_Finish_r(&raw);
        FCGX_Free(&raw, 1);
        active = false;
        fcgi_listener_access::get(owner)->busy = false;
    }
    void fallback() noexcept {
        FCGX_PutS("Status: 500 Internal Server Error\r\nContent-Type: text/plain\r\nContent-Length: 0\r\n\r\n", raw.out);
        finish();
    }
    ~fcgi_exchange_state() { if (active) fallback(); }
};
}

namespace scpp::fcgi {
using detail::fcgi_exchange_access;
using detail::fcgi_listener_access;

static void os_error(const char* operation) {
    throw std::system_error(errno, std::generic_category(), operation);
}
static string owned(std::string_view bytes) {
    return string{bytes.data(), static_cast<int64>(bytes.size())};
}
static std::string_view bytes(const string& value) {
    return {value.data(), static_cast<size_t>(value.size())};
}
static detail::fcgi_exchange_state& active(const fcgi_exchange& exchange) {
    auto* s = fcgi_exchange_access::get(exchange);
    if (!s || !s->active) throw ValueError("FastCGI exchange has finished");
    return *s;
}

fcgi_listener listen(const string& socket_path, int64 limit) {
    const auto path = bytes(socket_path);
    sockaddr_un address{};
    if (limit < 0 || path.empty() || path.front() != '/' || path.size() >= sizeof(address.sun_path) ||
        path.find('\0') != path.npos) throw ValueError("invalid FastCGI socket path or body limit");
    static const int init = FCGX_Init();
    if (init != 0) throw std::runtime_error("FastCGI initialization failed");
    auto* s = new detail::fcgi_listener_state;
    auto handle = fcgi_listener_access::adopt(s);
    s->path = path;
    s->limit = static_cast<size_t>(limit);
    s->fd = ::socket(AF_UNIX, SOCK_STREAM | SOCK_CLOEXEC, 0);
    if (s->fd < 0) os_error("FastCGI socket");
    address.sun_family = AF_UNIX;
    std::memcpy(address.sun_path, path.data(), path.size());
    if (::bind(s->fd, reinterpret_cast<sockaddr*>(&address), sizeof(address)) < 0) os_error("FastCGI bind");
    if (::lstat(s->path.c_str(), &s->identity) < 0) os_error("FastCGI socket identity");
    if (::listen(s->fd, 16) < 0) os_error("FastCGI listen");
    return handle;
}

static std::string_view param(FCGX_Request& raw, const char* name) {
    const char* value = FCGX_GetParam(name, raw.envp);
    return value ? value : "";
}
static char lower(char c) { return c >= 'A' && c <= 'Z' ? static_cast<char>(c + ('a'-'A')) : c; }

static void read_metadata(detail::fcgi_exchange_state& x) {
    auto& req = x.snapshot;
    auto method = param(x.raw, "REQUEST_METHOD");
    req.method = owned(method.empty() ? "GET" : method);
    auto uri = param(x.raw, "REQUEST_URI");
    auto path = uri.substr(0, uri.find('?'));
    req.path = owned(path.empty() ? "/" : path);
    req.query_string = owned(param(x.raw, "QUERY_STRING"));
    for (char** env = x.raw.envp; env && *env; ++env) {
        std::string_view pair{*env};
        const auto eq = pair.find('=');
        if (eq == pair.npos) continue;
        auto name = pair.substr(0, eq);
        if (name.starts_with("HTTP_")) name.remove_prefix(5);
        else if (name != "CONTENT_TYPE" && name != "CONTENT_LENGTH") continue;
        std::string key{name};
        for (char& c : key) c = c == '_' ? '-' : lower(c);
        req.header_names.append(owned(key));
        req.header_values.append(owned(pair.substr(eq + 1)));
    }
}

nullable_fcgi_exchange accept(const fcgi_listener& listener, int64 idle_ms) {
    auto* s = fcgi_listener_access::get(listener);
    if (!s || s->fd < 0 || s->busy || idle_ms < 0 || idle_ms > INT_MAX)
        throw ValueError("invalid FastCGI accept state or timeout");
    pollfd poll_fd{s->fd, POLLIN, 0};
    const int ready = ::poll(&poll_fd, 1, static_cast<int>(idle_ms));
    if (!ready || (ready < 0 && errno == EINTR)) return {};
    if (ready < 0) os_error("FastCGI poll");
    if (!(poll_fd.revents & POLLIN)) throw std::runtime_error("FastCGI listener unavailable");
    auto* x = new detail::fcgi_exchange_state;
    auto handle = fcgi_exchange_access::adopt(x);
    x->owner = listener;
    if (FCGX_InitRequest(&x->raw, s->fd, FCGI_FAIL_ACCEPT_ON_INTR) != 0 ||
        FCGX_Accept_r(&x->raw) != 0) throw std::runtime_error("FastCGI accept failed");
    s->busy = x->active = true;
    read_metadata(*x);
    std::string body;
    char chunk[8192];
    bool too_large = false;
    for (;;) {
        int n = FCGX_GetStr(chunk, sizeof(chunk), x->raw.in);
        if (n <= 0) break;
        if (static_cast<size_t>(n) > s->limit - body.size()) too_large = true;
        if (!too_large) body.append(chunk, static_cast<size_t>(n));
    }
    if (FCGX_GetError(x->raw.in)) throw std::runtime_error("FastCGI request read failed");
    if (too_large) {
        FCGX_PutS("Status: 413 Payload Too Large\r\nContent-Type: text/plain\r\nContent-Length: 0\r\n\r\n", x->raw.out);
        x->finish();
        return {};
    }
    x->snapshot.body = owned(body);
    return nullable_fcgi_exchange{std::move(handle)};
}

fcgi_request request(const fcgi_exchange& exchange) { return active(exchange).snapshot; }

void header(const fcgi_exchange& exchange, const string& name, const string& value) {
    auto& s = active(exchange);
    const auto key = bytes(name), val = bytes(value);
    std::string normalized;
    for (char c : key) {
        if (!((c >= 'A' && c <= 'Z') || (c >= 'a' && c <= 'z') || (c >= '0' && c <= '9') ||
              std::string_view("!#$%&'*+-.^_`|~").find(c) != std::string_view::npos)) throw ValueError("invalid FastCGI response header name");
        normalized += lower(c);
    }
    if (key.empty() || normalized == "status" || normalized == "content-length" || normalized == "transfer-encoding" ||
        normalized == "connection") throw ValueError("reserved or empty FastCGI response header");
    for (unsigned char c : val)
        if ((c < 32 && c != '\t') || c == 127) throw ValueError("invalid FastCGI response header value");
    if (normalized == "content-type" && s.content_type) throw ValueError("duplicate FastCGI content type");
    s.headers += std::string(key) + ": " + std::string(val) + "\r\n";
    if (normalized == "content-type") s.content_type = true;
}
static bool write_all(FCGX_Stream* out, std::string_view data) {
    while (!data.empty()) {
        const int n = static_cast<int>(std::min<size_t>(data.size(), INT_MAX));
        if (FCGX_PutStr(data.data(), n, out) != n) return false;
        data.remove_prefix(n);
    }
    return true;
}
void reply(const fcgi_exchange& exchange, int64 status, const string& body) {
    auto& s = active(exchange);
    if (status < 200 || status > 599) throw ValueError("invalid FastCGI response status");
    const std::string head = "Status: " + std::to_string(status) + "\r\n" + s.headers +
        (s.content_type ? "" : "Content-Type: application/json; charset=utf-8\r\n") +
        "Content-Length: " + std::to_string(body.size()) + "\r\n\r\n";
    const bool ok = write_all(s.raw.out, head) && write_all(s.raw.out, bytes(body)) && FCGX_FFlush(s.raw.out) == 0;
    s.finish();
    if (!ok) throw std::runtime_error("FastCGI response write failed");
}
void abandon(const fcgi_exchange& exchange) { active(exchange).fallback(); }
void close(const fcgi_listener& listener) {
    auto* s = fcgi_listener_access::get(listener);
    if (s && s->busy) throw ValueError("FastCGI listener has an active exchange");
    if (s) s->close_socket();
}
}
#include "fcgi_values.hpp"
