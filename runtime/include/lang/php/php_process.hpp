#pragma once

#include "lang/php/cli_context.hpp"

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

[[nodiscard]] inline int_t<> cli_argc() {
	return int_t<>(static_cast<std::int64_t>(cli_detail::g_cli_argc));
}

[[nodiscard]] inline mixed_t cli_argv() {
	auto out = table_();
	for (int index = 0; index < cli_detail::g_cli_argc; ++index) {
		const char* raw = cli_detail::g_cli_argv == nullptr ? nullptr : cli_detail::g_cli_argv[index];
		static_cast<void>(out->append(mixed_t(string_t(raw == nullptr ? "" : raw))));
	}
	return mixed_t(std::move(out));
}

[[nodiscard]] inline mixed_t cli_args() {
	return cli_argv();
}

[[nodiscard]] inline result_or_false<string_t> getenv(const string_t &name) {
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

	while (std::fgets(buffer, static_cast<int>(sizeof(buffer)), handle.get()) != nullptr) {
		output += buffer;
	}

	return string_t(std::move(output));
}

} // namespace scpp::php
