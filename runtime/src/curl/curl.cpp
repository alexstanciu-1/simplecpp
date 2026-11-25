#include "curl_provider.hpp"
#include <curl/curl.h>
#include <atomic>
#include <limits>
#include <memory>
#include <optional>
#include <string>
#include <utility>
#include <vector>

namespace scpp::detail {
struct curl_options {
    std::string url, method, agent{"simplecpp-curl/1.0"};
    std::optional<string> postfields;
    std::vector<std::string> headers;
    long timeout{}, connect_timeout{}, follow{}, post{}, verify_peer{1}, verify_host{2};
};
struct curl_handle_state {
    std::atomic<size_t> references{1};
    bool closed{};
    curl_options options;
    int64 code{};
    string message;
    std::optional<curl_response> response;
};
}

namespace scpp::curl {
using detail::curl_handle_access;
using detail::curl_handle_state;
static error_t failed(curl_handle_state* state, CURLcode code, const char* message) {
    string text{message};
    if (state) {
        state->code = code;
        state->message = text;
        state->response.reset();
    }
    return error_t{std::move(text)};
}
static error_t invalid(curl_handle_state* state, const char* reason = "invalid or closed curl handle") {
    return failed(state, CURLE_BAD_FUNCTION_ARGUMENT, reason);
}
static void clear_error(curl_handle_state& state) { state.code = 0; state.message.clear(); }
result_curl_handle init() {
    static const auto initialized = curl_global_init(CURL_GLOBAL_DEFAULT);
    if (initialized != CURLE_OK) return result_curl_handle{failed(nullptr, initialized, curl_easy_strerror(initialized))};
    return result_curl_handle{curl_handle_access::adopt(new curl_handle_state)};
}
result_curl_handle init(const string& url) {
    auto result = init();
    if (result.has_value()) {
        auto set = setopt_string(result.value(), 1, url);
        if (!set.has_value()) return result_curl_handle{set.error()};
    }
    return result;
}
result_bool_t setopt_string(const curl_handle& handle, int64 option, const string& value) {
    auto* s = curl_handle_access::get(handle);
    if (!s || s->closed) return result_bool_t{invalid(s)};
    auto& o = s->options;
    if (option == 5) o.postfields = value;
    else {
        std::string bytes(value.data(), value.size());
        if (bytes.find('\0') != bytes.npos) return result_bool_t{invalid(s, "curl text option contains NUL")};
        switch (option) {
        case 1: o.url = std::move(bytes); break;
        case 6: o.method = std::move(bytes); break;
        case 10: o.agent = std::move(bytes); break;
        default: return result_bool_t{invalid(s, "option does not accept a string")};
        }
    }
    clear_error(*s);
    return result_bool_t{true};
}
result_bool_t setopt_integer(const curl_handle& handle, int64 option, int64 value) {
    auto* s = curl_handle_access::get(handle);
    if (!s || s->closed) return result_bool_t{invalid(s)};
    auto& o = s->options;
    switch (option) {
    case 2: break; // Both source surfaces always return the captured body.
    case 4: o.post = value != 0; break;
    case 7: case 8:
        if (value < 0 || !std::in_range<long>(value)) return result_bool_t{invalid(s,"invalid timeout")};
        (option == 7 ? o.timeout : o.connect_timeout) = static_cast<long>(value);
        break;
    case 9: o.follow = value != 0; break;
    case 11: o.verify_peer = value != 0; break;
    case 12:
        if (value != 0 && value != 2) return result_bool_t{invalid(s,"verify host requires 0 or 2")};
        o.verify_host = static_cast<long>(value); break;
    default: return result_bool_t{invalid(s, "option does not accept an integer")};
    }
    clear_error(*s);
    return result_bool_t{true};
}
result_bool_t setopt_boolean(const curl_handle& h, int64 opt, bool_t v) { return setopt_integer(h, opt, v); }
result_bool_t setopt_headers(const curl_handle& h, int64 opt, const vector_string& values) {
    auto* s = curl_handle_access::get(h);
    if (!s || s->closed || opt != 3) return result_bool_t{invalid(s, "invalid handle or header option")};
    std::vector<std::string> headers;
    for (int64 i = 0; i < values.size(); ++i) {
        auto v = values.at(i);
        auto& header = headers.emplace_back(v.data(), v.size());
        if (header.find('\0') != header.npos) return result_bool_t{invalid(s, "curl header contains NUL")};
    }
    s->options.headers = std::move(headers);
    clear_error(*s);
    return result_bool_t{true};
}

struct transfer {
    std::string body;
    vector_string headers;
    curl_slist* request_headers{};
    ~transfer() { curl_slist_free_all(request_headers); }
};
template<bool header>
static size_t receive(char* data, size_t size, size_t count, void* context) noexcept {
    if (size && count > std::numeric_limits<size_t>::max() / size) return 0;
    auto bytes = size * count;
    auto& t = *static_cast<transfer*>(context);
    try {
        if constexpr (header) {
            auto end = bytes;
            while (end && (data[end-1] == '\n' || data[end-1] == '\r')) --end;
            if (end) t.headers.append(string{data, static_cast<int64>(end)});
        } else t.body.append(data, bytes);
    } catch (...) { return 0; }
    return bytes;
}
result_curl_response exec(const curl_handle& h) {
    auto* s = curl_handle_access::get(h);
    if (!s || s->closed) return result_curl_response{invalid(s)};
    auto& o = s->options;
    if (o.url.empty()) return result_curl_response{invalid(s, "curl URL is required")};
    transfer t;
    char message[CURL_ERROR_SIZE]{};
    std::unique_ptr<CURL, decltype(&curl_easy_cleanup)> easy(curl_easy_init(), curl_easy_cleanup);
    if (!easy) return result_curl_response{failed(s,CURLE_FAILED_INIT,"curl easy initialization failed")};
    CURLcode rc = CURLE_OK;
    auto set = [&](CURLoption option, auto value) {
        if (rc == CURLE_OK) rc = curl_easy_setopt(easy.get(), option, value);
    };
    set(CURLOPT_URL,o.url.c_str());
    set(CURLOPT_ERRORBUFFER,message);
    set(CURLOPT_NOSIGNAL,1L);
    set(CURLOPT_FOLLOWLOCATION,o.follow);
    set(CURLOPT_SSL_VERIFYPEER,o.verify_peer);
    set(CURLOPT_SSL_VERIFYHOST,o.verify_host);
    set(CURLOPT_TIMEOUT,o.timeout);
    set(CURLOPT_CONNECTTIMEOUT,o.connect_timeout);
    if (!o.agent.empty()) set(CURLOPT_USERAGENT,o.agent.c_str());
    if (o.post || o.postfields) {
        set(CURLOPT_POST,1L);
        set(CURLOPT_POSTFIELDSIZE_LARGE,static_cast<curl_off_t>(o.postfields ? o.postfields->size() : 0));
        set(CURLOPT_POSTFIELDS,o.postfields ? o.postfields->data() : "");
    }
    if (!o.method.empty()) set(CURLOPT_CUSTOMREQUEST,o.method.c_str());
    for (const auto& header : o.headers) {
        auto* next = curl_slist_append(t.request_headers,header.c_str());
        if (!next) return result_curl_response{failed(s,CURLE_OUT_OF_MEMORY,"curl header allocation failed")};
        t.request_headers = next;
    }
    if (t.request_headers) set(CURLOPT_HTTPHEADER,t.request_headers);
    set(CURLOPT_WRITEFUNCTION,&receive<false>);
    set(CURLOPT_WRITEDATA,&t);
    set(CURLOPT_HEADERFUNCTION,&receive<true>);
    set(CURLOPT_HEADERDATA,&t);
    if (rc == CURLE_OK) rc = curl_easy_perform(easy.get());
    curl_response response;
    auto get = [&](CURLINFO info, auto* dest) {
        if (rc == CURLE_OK) rc = curl_easy_getinfo(easy.get(), info, dest);
    };
    long status{}, header_size{}, request_size{}, redirects{};
    char *url{}, *content_type{};
    double seconds{};
    get(CURLINFO_RESPONSE_CODE,&status);
    get(CURLINFO_HEADER_SIZE,&header_size);
    get(CURLINFO_REQUEST_SIZE,&request_size);
    get(CURLINFO_REDIRECT_COUNT,&redirects);
    get(CURLINFO_EFFECTIVE_URL,&url);
    get(CURLINFO_CONTENT_TYPE,&content_type);
    get(CURLINFO_TOTAL_TIME,&seconds);
    if (rc != CURLE_OK) return result_curl_response{failed(s,rc,*message ? message : curl_easy_strerror(rc))};
    response.body = string{t.body.data(), static_cast<int64>(t.body.size())};
    response.headers = std::move(t.headers);
    response.status_code = status;
    response.header_size = header_size;
    response.request_size = request_size;
    response.redirect_count = redirects;
    response.total_time_ms = static_cast<int64>(seconds * 1000);
    if (url) response.effective_url = string{url};
    if (content_type) response.content_type = string{content_type};
    s->response = response;
    clear_error(*s);
    return result_curl_response{std::move(response)};
}
result_mixed getinfo(const curl_handle& h, int64 info) {
    auto* s = curl_handle_access::get(h);
    if (!s || s->closed || !s->response) return result_mixed{invalid(s, "no curl response available")};
    const auto& r = *s->response;
    clear_error(*s);
    switch (info) {
    case 101: return result_mixed{mixed::from_integer(r.status_code)};
    case 102: return result_mixed{mixed::from_string(r.effective_url)};
    case 103: return result_mixed{mixed::from_string(r.content_type)};
    case 104: return result_mixed{mixed::from_integer(r.total_time_ms)};
    case 105: return result_mixed{mixed::from_integer(r.header_size)};
    case 106: return result_mixed{mixed::from_integer(r.request_size)};
    case 107: return result_mixed{mixed::from_integer(r.redirect_count)};
    default: return result_mixed{invalid(s,"unknown curl info selector")};
    }
}
int64 errno_code(const curl_handle& h) { auto* s = curl_handle_access::get(h); return s ? s->code : 0; }
string error_string(const curl_handle& h) { auto* s = curl_handle_access::get(h); return s ? s->message : string{}; }
string strerror(int64 code) { return string{std::in_range<int>(code) && code >= 0 && code < CURL_LAST ? curl_easy_strerror(static_cast<CURLcode>(code)) : "Unknown error"}; }
result_bool_t reset(const curl_handle& h) {
    auto* s = curl_handle_access::get(h);
    if (!s || s->closed) return result_bool_t{invalid(s)};
    s->options = {}; s->response.reset(); clear_error(*s);
    return result_bool_t{true};
}
result_bool_t close(const curl_handle& h) {
    auto r = reset(h);
    if (r.has_value()) curl_handle_access::get(h)->closed = true;
    return r;
}

static result_or_false_curl_handle php_handle(const result_curl_handle& r) {
    if (!r.has_value()) return {};
    return result_or_false_curl_handle{r.value()};
}
result_or_false_curl_handle php_init() { return php_handle(init()); }
result_or_false_curl_handle php_init(const string& url) { return php_handle(init(url)); }
result_or_false_string php_exec(const curl_handle& h) {
    auto r = exec(h);
    if (!r.has_value()) return {};
    return result_or_false_string{r.value().body};
}
bool_t php_setopt_string(const curl_handle& h, int64 opt, const string& v) { return setopt_string(h,opt,v).has_value(); }
bool_t php_setopt_integer(const curl_handle& h, int64 opt, int64 v) { return setopt_integer(h,opt,v).has_value(); }
bool_t php_setopt_boolean(const curl_handle& h, int64 opt, bool_t v) { return setopt_boolean(h,opt,v).has_value(); }
bool_t php_setopt_headers(const curl_handle& h, int64 opt, const vector_string& v) { return setopt_headers(h,opt,v).has_value(); }
bool_t php_setopt_mixed(const curl_handle& h, int64 opt, const mixed& v) {
    switch (v.kind()) {
    case boxed_kind::string: return php_setopt_string(h,opt,v.get_string());
    case boxed_kind::integer: return php_setopt_integer(h,opt,v.get_integer());
    case boxed_kind::boolean: return php_setopt_boolean(h,opt,v.get_boolean());
    case boxed_kind::container:
        if (v.is_packed_container()) {
            vector_string headers;
            for (int64 i = 0; i < v.container_size(); ++i) {
                auto item = v.read(i);
                if (item.kind() != boxed_kind::string) {
                    invalid(curl_handle_access::get(h),"curl header must be a string"); return false;
                }
                headers.append(item.get_string());
            }
            return php_setopt_headers(h,opt,headers);
        }
        break;
    default: break;
    }
    invalid(curl_handle_access::get(h),"unsupported curl option value");
    return false;
}
mixed php_getinfo(const curl_handle& h, int64 selector) {
    auto result = getinfo(h, selector);
    return result.has_value() ? result.value() : mixed::from_boolean(false);
}
mixed php_getinfo(const curl_handle& h) {
    auto status = getinfo(h,101);
    if (!status.has_value()) return mixed::from_boolean(false);
    auto table = mixed::container();
    table.set(string{"http_code"},status.value());
    table.set(string{"response_code"},status.value());
    const char* keys[]{"effective_url","content_type","total_time_ms","header_size","request_size","redirect_count"};
    for (int64 i = 0; i < 6; ++i) table.set(string{keys[i]}, getinfo(h,102+i).value());
    return table;
}
bool_t php_reset(const curl_handle& h) { return reset(h).has_value(); }
bool_t php_close(const curl_handle& h) { return close(h).has_value(); }
}
#include "curl_values.hpp"
