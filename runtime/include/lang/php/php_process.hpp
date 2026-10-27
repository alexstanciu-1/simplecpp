#pragma once

#include "scpp/detail/cli_arguments.hpp"
#include "core/string_support.hpp"

#include "scpp/bool_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"

#include <cstdio>
#include <cstdlib>
#include <memory>
#include <string>

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

[[nodiscard]] inline result_or_false<string_t> getenv(const string_t &name) {
	if (name.native_value().find('\0') != std::string::npos) {
		throw scpp::ValueError("getenv name must not contain NUL");
	}
	const char* raw = std::getenv(name.native_value().c_str());
	if (raw == nullptr) {
		return false_sentinel;
	}
	return string_t(raw);
}

[[nodiscard]] inline result_or_false<string_t> shell_exec(const string_t &command) {
	FILE* raw = process_detail::shell_popen(command.native_value().c_str());
	if (raw == nullptr) {
		return false_sentinel;
	}

	std::unique_ptr<FILE, int(*)(FILE*)> handle(raw, process_detail::shell_pclose);
	std::string output;
	char buffer[4096];

	std::size_t byte_count = 0;
	while ((byte_count = std::fread(buffer, 1, sizeof(buffer), handle.get())) != 0) {
		output.append(buffer, byte_count);
	}

	return string_t(std::move(output));
}

} // namespace scpp::php
