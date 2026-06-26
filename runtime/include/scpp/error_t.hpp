#pragma once

#include "scpp/string_t.hpp"
#include "scpp/int_t.hpp"

namespace scpp {

// Structured in-band runtime error payload for result<T>.
class error_t final {
private:
	string_t message_{};
	int_t<> line_{};
	string_t file_{};

public:
	error_t() = default;
	error_t(const string_t &message, const int_t<> &line = static_cast<int_t<>>(0), const string_t &file = string_t(""))
		: message_(message), line_(line), file_(file) {}

	[[nodiscard]] const string_t &get_message() const noexcept { return message_; }
	[[nodiscard]] const int_t<> &get_line() const noexcept { return line_; }
	[[nodiscard]] const string_t &get_file() const noexcept { return file_; }
};

// Sentinel tag/object used by result<T> for explicit error-state comparisons.
struct error_sentinel_t final {};

inline constexpr error_sentinel_t error{};

} // namespace scpp
