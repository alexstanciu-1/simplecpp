#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"

namespace scpp {

// Semantic string wrapper.
//
// Enforces:
// - source-level strings stay inside scpp::string_t
// - append uses the wrapped string representation
class string_t final {
private:
	std::string value_;

public:
	string_t() = default;

	explicit string_t(std::string value)
		: value_(std::move(value)) {
	}

	explicit string_t(std::string_view value)
		: value_(value) {
	}

	explicit string_t(const char *value)
		: value_(value != nullptr ? value : "") {
	}

	[[nodiscard]] const std::string &native_value() const noexcept {
		return value_;
	}

	[[nodiscard]] std::size_t size() const noexcept {
		return value_.size();
	}

	[[nodiscard]] bool_t empty() const noexcept {
		return bool_t(value_.empty());
	}

	[[nodiscard]] string_t slice(std::size_t start, std::size_t end_exclusive) const {
		// Produces a bounded half-open substring view for runtime/internal helpers.
		// How: the bounds are clamped once here so wrapper code can normalize indices without duplicating std::string details.
		const auto size = value_.size();
		const auto clamped_start = start < size ? start : size;
		auto clamped_end = end_exclusive < size ? end_exclusive : size;
		if (clamped_end < clamped_start) {
			clamped_end = clamped_start;
		}
		return string_t(value_.substr(clamped_start, clamped_end - clamped_start));
	}

	void append(const string_t &value) {
		value_ += value.value_;
	}

	void _unset_() noexcept {
		value_.clear();
	}
};

} // namespace scpp
