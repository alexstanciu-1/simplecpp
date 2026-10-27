#pragma once

#include "scpp/native_value_errors.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"
#include <cstdio>
#include <cstdlib>
#include <memory>
#include <string>
#include <utility>

namespace scpp::php {

namespace process_detail {

#if defined(_WIN32)
inline constexpr const char* shell_popen_mode() {
	return "rb";
}

inline FILE* shell_popen(const char* command) {
	return _popen(command, shell_popen_mode());
}

inline int shell_pclose(FILE* handle) {
	return _pclose(handle);
}
#else
inline constexpr const char* shell_popen_mode() {
	return "r";
}

inline FILE* shell_popen(const char* command) {
	return popen(command, shell_popen_mode());
}

inline int shell_pclose(FILE* handle) {
	return pclose(handle);
}
#endif

} // namespace process_detail

namespace process_detail {

template <typename Output, typename Make>
[[nodiscard]] inline result_or_false<Output> getenv_owned(const string_t &name, Make &&make) {
    if (name.native_value().find('\0') != std::string::npos) {
        throw scpp::ValueError("getenv name must not contain NUL");
    }
    const char *raw = std::getenv(name.native_value().c_str());
    if (raw == nullptr) return false_sentinel;
    return make(std::string(raw));
}

// Own an opened pipe before reading or constructing its result. A null pipe is
// the false state; EOF, empty bytes and nonzero child exit status are not failure.
template <typename Output, typename Make>
[[nodiscard]] inline result_or_false<Output> capture_pipe_owned(FILE *raw, int (*close)(FILE *), Make &&make) {
    if (raw == nullptr) return false_sentinel;
    std::unique_ptr<FILE, int(*)(FILE*)> handle(raw, close);
    std::string output;
    char buffer[4096];
    std::size_t byte_count = 0;
    while ((byte_count = std::fread(buffer, 1, sizeof(buffer), handle.get())) != 0) {
        output.append(buffer, byte_count);
    }
    return make(std::move(output));
}

template <typename Output, typename Make>
[[nodiscard]] inline result_or_false<Output> shell_exec_owned(const string_t &command, Make &&make) {
    return capture_pipe_owned<Output>(shell_popen(command.native_value().c_str()), shell_pclose, std::forward<Make>(make));
}

} // namespace process_detail
} // namespace scpp::php
