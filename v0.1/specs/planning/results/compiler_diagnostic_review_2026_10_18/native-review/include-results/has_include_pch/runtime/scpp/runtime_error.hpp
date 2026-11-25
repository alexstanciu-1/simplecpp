#pragma once

#include <array>
#include <cctype>
#include <cstdlib>
#include <cstdio>
#include <exception>
#include <iostream>
#include <optional>
#include <ostream>
#include <stdexcept>
#include <string>
#include <string_view>
#include <utility>
#include <vector>

#if defined(__linux__) || defined(__APPLE__)
#include <execinfo.h>
#include <dlfcn.h>
#include <unistd.h>
#endif

namespace scpp {

struct runtime_error_detail_t {
	std::string key;
	std::string value;
};

struct runtime_trace_frame_t {
	std::string module_path;
	std::string relative_address;
};

inline FILE *runtime_error_popen(const char *command) {
#if defined(_WIN32)
	return _popen(command, "r");
#else
	return popen(command, "r");
#endif
}

inline int runtime_error_pclose(FILE *pipe) {
#if defined(_WIN32)
	return _pclose(pipe);
#else
	return pclose(pipe);
#endif
}

inline std::vector<runtime_trace_frame_t> capture_runtime_trace_frames() {
	std::vector<runtime_trace_frame_t> trace;
#if defined(__linux__) || defined(__APPLE__)
	std::array<void *, 64> frames{};
	const int frameCount = ::backtrace(frames.data(), static_cast<int>(frames.size()));
	for (int index = 0; index < frameCount; ++index) {
		const auto absoluteAddress = reinterpret_cast<std::uintptr_t>(frames[static_cast<std::size_t>(index)]);
		if (absoluteAddress == 0) {
			continue;
		}
		Dl_info info{};
		if (::dladdr(frames[static_cast<std::size_t>(index)], &info) == 0 || info.dli_fname == nullptr || info.dli_fbase == nullptr) {
			continue;
		}
		const auto moduleBase = reinterpret_cast<std::uintptr_t>(info.dli_fbase);
		if (absoluteAddress <= moduleBase) {
			continue;
		}
		const auto relativeAddress = (absoluteAddress - moduleBase) - 1u;
		char addressBuf[32];
		std::snprintf(addressBuf, sizeof(addressBuf), "0x%zx", static_cast<std::size_t>(relativeAddress));
		trace.push_back(runtime_trace_frame_t{
			std::string(info.dli_fname),
			std::string(addressBuf),
		});
	}
#endif
	return trace;
}

class runtime_error final : public std::runtime_error {
public:
	runtime_error(
		std::string message,
		std::string code = {},
		std::string component = {},
		std::string operator_symbol = {},
		std::vector<runtime_error_detail_t> details = {}
	)
		: std::runtime_error(std::move(message))
		, code_(std::move(code))
		, component_(std::move(component))
		, operator_symbol_(std::move(operator_symbol))
		, details_(std::move(details))
		, trace_(capture_runtime_trace_frames()) {}

	[[nodiscard]] const std::string &code() const noexcept { return code_; }
	[[nodiscard]] const std::string &component() const noexcept { return component_; }
	[[nodiscard]] const std::string &operator_symbol() const noexcept { return operator_symbol_; }
	[[nodiscard]] const std::vector<runtime_error_detail_t> &details() const noexcept { return details_; }
	[[nodiscard]] const std::vector<runtime_trace_frame_t> &trace() const noexcept { return trace_; }

private:
	std::string code_;
	std::string component_;
	std::string operator_symbol_;
	std::vector<runtime_error_detail_t> details_;
	std::vector<runtime_trace_frame_t> trace_;
};

inline constexpr int default_call_depth_limit = 4096;
inline thread_local int g_call_depth = 0;

class call_depth_guard final {
public:
	call_depth_guard(const char *function_name, const char *generated_file, int generated_line)
		: active_(true) {
		++g_call_depth;
		if (g_call_depth <= configured_limit()) {
			return;
		}
		--g_call_depth;
		active_ = false;
		throw runtime_error(
			std::string("Maximum call depth exceeded while calling `") + (function_name == nullptr ? "<unknown>" : function_name) + "`.",
			"max_call_depth_exceeded",
			"runtime",
			"call",
			{
				{"function", function_name == nullptr ? "<unknown>" : function_name},
				{"max_call_depth", std::to_string(configured_limit())},
				{"source_file", generated_file == nullptr ? "" : generated_file},
				{"source_line", std::to_string(generated_line)},
			}
		);
	}

	call_depth_guard(const call_depth_guard &) = delete;
	call_depth_guard &operator=(const call_depth_guard &) = delete;

	~call_depth_guard() noexcept {
		if (active_) {
			--g_call_depth;
		}
	}

private:
	static int configured_limit() {
#ifdef SCPP_MAX_CALL_DEPTH
		return SCPP_MAX_CALL_DEPTH;
#else
		return default_call_depth_limit;
#endif
	}

	bool active_;
};

inline bool runtime_error_json_enabled() {
	const char *value = std::getenv("SCPP_ERROR_FORMAT");
	return value != nullptr && std::string_view(value) == "json";
}

inline bool runtime_error_debug_trace_enabled() {
	const char *value = std::getenv("SCPP_DEBUG_TRACE");
	return value != nullptr && std::string_view(value) == "1";
}

inline std::string runtime_error_json_escape(std::string_view value) {
	std::string out;
	out.reserve(value.size() + 8);
	for (const char ch : value) {
		switch (ch) {
			case '\\': out += "\\\\"; break;
			case '"': out += "\\\""; break;
			case '\b': out += "\\b"; break;
			case '\f': out += "\\f"; break;
			case '\n': out += "\\n"; break;
			case '\r': out += "\\r"; break;
			case '\t': out += "\\t"; break;
			default:
				if (static_cast<unsigned char>(ch) < 0x20) {
					constexpr char hex[] = "0123456789abcdef";
					out += "\\u00";
					out += hex[(static_cast<unsigned char>(ch) >> 4) & 0x0f];
					out += hex[static_cast<unsigned char>(ch) & 0x0f];
				} else {
					out += ch;
				}
		}
	}
	return out;
}

inline bool runtime_error_has_detail_key(const std::vector<runtime_error_detail_t> &details, std::string_view key) {
	for (const auto &detail : details) {
		if (detail.key == key) {
			return true;
		}
	}
	return false;
}

inline std::string runtime_error_shell_escape(std::string_view value) {
	std::string out = "'";
	for (const char ch : value) {
		if (ch == '\'') {
			out += "'\\''";
		} else {
			out.push_back(ch);
		}
	}
	out += "'";
	return out;
}

inline std::string runtime_error_normalize_path(std::string path) {
	const bool absolute = !path.empty() && path.front() == '/';
	std::vector<std::string> parts;
	std::size_t start = 0;
	while (start <= path.size()) {
		const std::size_t end = path.find('/', start);
		const std::string part = path.substr(start, end == std::string::npos ? std::string::npos : end - start);
		if (!part.empty() && part != ".") {
			if (part == "..") {
				if (!parts.empty()) {
					parts.pop_back();
				}
			} else {
				parts.push_back(part);
			}
		}
		if (end == std::string::npos) {
			break;
		}
		start = end + 1;
	}
	std::string out = absolute ? "/" : "";
	for (std::size_t index = 0; index < parts.size(); ++index) {
		if (index > 0) {
			out += "/";
		}
		out += parts[index];
	}
	return out.empty() ? (absolute ? "/" : ".") : out;
}

inline std::optional<std::vector<runtime_error_detail_t>> recover_generated_location_details_from_trace(const std::vector<runtime_trace_frame_t> &trace) {
	for (const auto &frame : trace) {
		const std::string command = "addr2line -C -f -e " + runtime_error_shell_escape(frame.module_path) + " " + frame.relative_address;
		FILE *pipe = runtime_error_popen(command.c_str());
		if (pipe == nullptr) {
			continue;
		}
		std::string output;
		char buffer[512];
		while (std::fgets(buffer, sizeof(buffer), pipe) != nullptr) {
			output += buffer;
		}
		runtime_error_pclose(pipe);

		const std::size_t newlinePos = output.find('\n');
		if (newlinePos == std::string::npos || newlinePos + 1 >= output.size()) {
			continue;
		}
		std::string location = output.substr(newlinePos + 1);
		while (!location.empty() && (location.back() == '\n' || location.back() == '\r')) {
			location.pop_back();
		}
		if (location.empty() || location == "??:0") {
			continue;
		}
		const std::size_t lineSep = location.rfind(':');
		if (lineSep == std::string::npos) {
			continue;
		}
		const std::string file = runtime_error_normalize_path(location.substr(0, lineSep));
		const std::string line = location.substr(lineSep + 1);
		if (file.find("/.prism/generated/") == std::string::npos) {
			continue;
		}
		if (line.empty() || line == "0" || line == "?") {
			continue;
		}
		return std::vector<runtime_error_detail_t>{
			{"generated_file", file},
			{"generated_line", line},
		};
	}
	return std::nullopt;
}

inline std::vector<std::string> collect_runtime_debug_trace_lines() {
	std::vector<std::string> trace;
	return trace;
}

inline std::string runtime_error_compact_module_label(std::string_view modulePath) {
	if (modulePath.empty()) {
		return "<unknown>";
	}
	const std::size_t slashPos = modulePath.find_last_of('/');
	return slashPos == std::string_view::npos ? std::string(modulePath) : std::string(modulePath.substr(slashPos + 1));
}

inline std::string runtime_error_trim_copy(std::string value) {
	while (!value.empty() && std::isspace(static_cast<unsigned char>(value.front())) != 0) {
		value.erase(value.begin());
	}
	while (!value.empty() && std::isspace(static_cast<unsigned char>(value.back())) != 0) {
		value.pop_back();
	}
	return value;
}

inline std::optional<std::string> symbolize_runtime_trace_frame(const runtime_trace_frame_t &frame) {
#if defined(__linux__) || defined(__APPLE__)
	const std::string command = "addr2line -C -f -e " + runtime_error_shell_escape(frame.module_path) + " " + frame.relative_address;
	FILE *pipe = runtime_error_popen(command.c_str());
	if (pipe == nullptr) {
		return std::nullopt;
	}
	std::string output;
	char buffer[512];
	while (::fgets(buffer, sizeof(buffer), pipe) != nullptr) {
		output += buffer;
	}
	runtime_error_pclose(pipe);

	const std::size_t firstNewline = output.find('\n');
	if (firstNewline == std::string::npos) {
		return std::nullopt;
	}
	std::string functionName = runtime_error_trim_copy(output.substr(0, firstNewline));
	std::string location = runtime_error_trim_copy(output.substr(firstNewline + 1));
	if (functionName.empty() || functionName == "??") {
		functionName = runtime_error_compact_module_label(frame.module_path);
	}
	if (location.empty() || location == "??:0") {
		return functionName + " @" + frame.relative_address;
	}
	return functionName + " at " + runtime_error_normalize_path(location);
#else
	(void) frame;
	return std::nullopt;
#endif
}

inline std::vector<std::string> collect_runtime_debug_trace_lines(const std::vector<runtime_trace_frame_t> &frames) {
	std::vector<std::string> trace;
	for (const auto &frame : frames) {
		if (auto symbolized = symbolize_runtime_trace_frame(frame); symbolized.has_value()) {
			trace.push_back(*symbolized);
			continue;
		}
		trace.push_back(runtime_error_compact_module_label(frame.module_path) + " @" + frame.relative_address);
	}
	return trace;
}

inline std::string format_runtime_error_json(const std::exception &exception) {
	const auto *structured = dynamic_cast<const runtime_error *>(&exception);
	std::string out = "{\"error\":{";
	out += "\"message\":\"" + runtime_error_json_escape(exception.what()) + "\"";
	if (structured != nullptr) {
		std::vector<runtime_error_detail_t> details = structured->details();
		if (!runtime_error_has_detail_key(details, "generated_file")) {
			if (auto recovered = recover_generated_location_details_from_trace(structured->trace()); recovered.has_value()) {
				for (const auto &detail : *recovered) {
					if (!runtime_error_has_detail_key(details, detail.key)) {
						details.push_back(detail);
					}
				}
			}
		}
		if (!structured->code().empty()) {
			out += ",\"code\":\"" + runtime_error_json_escape(structured->code()) + "\"";
		}
		if (!structured->component().empty()) {
			out += ",\"component\":\"" + runtime_error_json_escape(structured->component()) + "\"";
		}
		if (!structured->operator_symbol().empty()) {
			out += ",\"operator\":\"" + runtime_error_json_escape(structured->operator_symbol()) + "\"";
		}
		if (!details.empty()) {
			out += ",\"details\":{";
			bool first = true;
			for (const auto &detail : details) {
				if (!first) {
					out += ',';
				}
				first = false;
				out += "\"" + runtime_error_json_escape(detail.key) + "\":\"" + runtime_error_json_escape(detail.value) + "\"";
			}
			out += '}';
		}
	}
	if (runtime_error_debug_trace_enabled()) {
		out += ",\"trace\":[";
		bool first = true;
		for (const auto &traceLine : structured != nullptr ? collect_runtime_debug_trace_lines(structured->trace()) : std::vector<std::string>{}) {
			if (!first) {
				out += ',';
			}
			first = false;
			out += "\"" + runtime_error_json_escape(traceLine) + "\"";
		}
		out += "]";
	}
	out += "}}";
	return out;
}

inline void print_runtime_exception(const std::exception &exception, std::ostream &stream = std::cerr) {
	if (runtime_error_json_enabled()) {
		stream << format_runtime_error_json(exception) << '\n';
		return;
	}
	stream << exception.what() << '\n';
}

} // namespace scpp
