#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/support/utf8.hpp"

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

	[[nodiscard]] std::size_t byte_size() const noexcept {
		return value_.size();
	}

	[[nodiscard]] std::size_t byte_capacity() const noexcept {
		return value_.capacity();
	}

	[[nodiscard]] std::size_t estimated_storage_bytes() const noexcept {
		return sizeof(*this) + value_.capacity();
	}

	[[nodiscard]] bool is_valid_utf8() const {
		return utf8::is_valid(value_);
	}

	[[nodiscard]] std::size_t length_cp() const {
		return utf8::length_cp(value_);
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

	[[nodiscard]] string_t substr_cp(std::size_t start, std::size_t length) const {
		// Produces one substring in codepoint space when the bytes are valid UTF-8.
		// How: valid text uses utf8cpp boundaries; invalid bytes fall back to byte slicing so source data is preserved.
		return string_t(utf8::substr_cp(value_, start, length));
	}

	[[nodiscard]] string_t substr_cp(std::size_t start) const {
		// Produces one substring from the requested codepoint through the end of the current value.
		// How: valid UTF-8 walks codepoints; invalid input falls back to byte slicing.
		return string_t(utf8::substr_cp_to_end(value_, start));
	}

	void append(const string_t &value) {
		value_ += value.value_;
	}

	[[nodiscard]] std::string release_native() noexcept {
		std::string out = std::move(value_);
		value_.clear();
		return out;
	}

	void _unset_() noexcept {
		value_.clear();
	}
};

} // namespace scpp
