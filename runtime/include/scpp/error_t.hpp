#pragma once

#include "scpp/string_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/bool_t.hpp"

namespace scpp {

// Structured in-band runtime error payload for result<T>.
class error_t final {
private:
	bool present_ = false;
	string_t message_{};
	int_t<> line_{};
	string_t file_{};

public:
	static const error_t no_error;
	error_t() = default;
	error_t(const string_t &message, const int_t<> &line = static_cast<int_t<>>(0), const string_t &file = string_t(""))
		: present_(true), message_(message), line_(line), file_(file) {}

	[[nodiscard]] bool_t has_error() const noexcept { return bool_t(present_); }

	[[nodiscard]] const string_t &get_message() const noexcept { return message_; }
	[[nodiscard]] const int_t<> &get_line() const noexcept { return line_; }
	[[nodiscard]] const string_t &get_file() const noexcept { return file_; }

	// Value-owned errors support the same source member-call syntax as other runtime handles.
	[[nodiscard]] error_t *operator->() noexcept { return this; }
	[[nodiscard]] const error_t *operator->() const noexcept { return this; }
};

inline const error_t error_t::no_error{};

// Sentinel tag/object used by result<T> for explicit error-state comparisons.
struct error_sentinel_t final {};

inline constexpr error_sentinel_t error{};

} // namespace scpp
