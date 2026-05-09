#pragma once

#include <cstdlib>
#include <exception>
#include <iostream>
#include <ostream>
#include <stdexcept>
#include <string>
#include <string_view>
#include <type_traits>
#include <utility>
#include <vector>

namespace scpp {

struct runtime_error_detail_t {
	std::string key;
	std::string value;
};

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
		, details_(std::move(details)) {}

	[[nodiscard]] const std::string &code() const noexcept { return code_; }
	[[nodiscard]] const std::string &component() const noexcept { return component_; }
	[[nodiscard]] const std::string &operator_symbol() const noexcept { return operator_symbol_; }
	[[nodiscard]] const std::vector<runtime_error_detail_t> &details() const noexcept { return details_; }

private:
	std::string code_;
	std::string component_;
	std::string operator_symbol_;
	std::vector<runtime_error_detail_t> details_;
};

inline bool runtime_error_json_enabled() {
	const char *value = std::getenv("SCPP_ERROR_FORMAT");
	return value != nullptr && std::string_view(value) == "json";
}

inline bool runtime_error_debug_trace_enabled() {
	const char *value = std::getenv("SCPP_DEBUG_TRACE");
	return value != nullptr && std::string_view(value) == "1";
}

inline void runtime_error_add_detail_if_missing(
	std::vector<runtime_error_detail_t> &details,
	std::string key,
	std::string value
) {
	if (value.empty()) {
		return;
	}
	for (const auto &detail : details) {
		if (detail.key == key) {
			return;
		}
	}
	details.push_back(runtime_error_detail_t{std::move(key), std::move(value)});
}

template <typename Fn>
decltype(auto) with_runtime_context(
	Fn &&fn,
	const char *source_file,
	int source_line,
	const char *expression,
	const char *expected_type,
	const char *operation
) {
	try {
		return std::forward<Fn>(fn)();
	} catch (const runtime_error &error) {
		auto details = error.details();
		runtime_error_add_detail_if_missing(details, "source_file", source_file == nullptr ? "" : source_file);
		if (source_line > 0) {
			runtime_error_add_detail_if_missing(details, "source_line", std::to_string(source_line));
		}
		runtime_error_add_detail_if_missing(details, "expression", expression == nullptr ? "" : expression);
		runtime_error_add_detail_if_missing(details, "expected_type", expected_type == nullptr ? "" : expected_type);
		runtime_error_add_detail_if_missing(details, "operation", operation == nullptr ? "" : operation);
		throw runtime_error(error.what(), error.code(), error.component(), error.operator_symbol(), std::move(details));
	}
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

inline std::string format_runtime_error_json(const std::exception &exception) {
	const auto *structured = dynamic_cast<const runtime_error *>(&exception);
	std::string out = "{\"error\":{";
	out += "\"message\":\"" + runtime_error_json_escape(exception.what()) + "\"";
	if (structured != nullptr) {
		if (!structured->code().empty()) {
			out += ",\"code\":\"" + runtime_error_json_escape(structured->code()) + "\"";
		}
		if (!structured->component().empty()) {
			out += ",\"component\":\"" + runtime_error_json_escape(structured->component()) + "\"";
		}
		if (!structured->operator_symbol().empty()) {
			out += ",\"operator\":\"" + runtime_error_json_escape(structured->operator_symbol()) + "\"";
		}
		if (!structured->details().empty()) {
			out += ",\"details\":{";
			bool first = true;
			for (const auto &detail : structured->details()) {
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
		out += ",\"trace\":[]";
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
