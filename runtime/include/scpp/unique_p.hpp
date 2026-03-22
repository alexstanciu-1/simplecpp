#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullptr_t.hpp"

namespace scpp {

// Unique ownership semantic wrapper.
//
// Enforces:
// - single-owner pointer semantics at the runtime level
// - copy is forbidden, move is allowed
// - null comparisons use runtime sentinels
// - dereference checks fail explicitly on null access
template <typename T>
class unique_p final {
private:
	std::unique_ptr<T> value_;

public:
	unique_p() = default;

	// Sentinel constructors normalize null-like values to an empty unique_ptr.
	unique_p(null_t) noexcept
		: value_(nullptr) {
	}

	unique_p(nullptr_t) noexcept
		: value_(nullptr) {
	}

	// Explicit native constructor for controlled boundary crossing.
	explicit unique_p(std::unique_ptr<T> value) noexcept
		: value_(std::move(value)) {
	}

	// Preserve std::unique_ptr ownership rules.
	unique_p(unique_p &&) noexcept = default;
	unique_p &operator=(unique_p &&) noexcept = default;

	unique_p(const unique_p &) = delete;
	unique_p &operator=(const unique_p &) = delete;

	// Presence query returned as bool_t.
	[[nodiscard]] bool_t has_value() const noexcept {
		return bool_t(static_cast<bool>(value_));
	}

	// Raw pointer access for integration code.
	[[nodiscard]] T *get() const noexcept {
		return value_.get();
	}

	// Checked dereference.
	T &deref() const {
		if (!value_) {
			throw std::runtime_error("scpp::unique_p dereference on null");
		}
		return *value_;
	}

	// Member-access helper for generated code.
	T *arrow() const noexcept {
		return value_.get();
	}

	// Native C++ member-access bridge used by generated object calls.
	T *operator->() const noexcept {
		return value_.get();
	}

	// Controlled access to the native smart pointer.
	[[nodiscard]] const std::unique_ptr<T> &native_value() const noexcept {
		return value_;
	}

	[[nodiscard]] std::unique_ptr<T> &native_value() noexcept {
		return value_;
	}

	// Sentinel-aware equality/inequality.
	[[nodiscard]] friend bool_t operator==(const unique_p<T> &left, null_t) noexcept {
		return bool_t(left.value_ == nullptr);
	}

	[[nodiscard]] friend bool_t operator==(null_t, const unique_p<T> &right) noexcept {
		return bool_t(right.value_ == nullptr);
	}

	[[nodiscard]] friend bool_t operator!=(const unique_p<T> &left, null_t) noexcept {
		return bool_t(left.value_ != nullptr);
	}

	[[nodiscard]] friend bool_t operator!=(null_t, const unique_p<T> &right) noexcept {
		return bool_t(right.value_ != nullptr);
	}

	[[nodiscard]] friend bool_t operator==(const unique_p<T> &left, nullptr_t) noexcept {
		return bool_t(left.value_ == nullptr);
	}

	[[nodiscard]] friend bool_t operator==(nullptr_t, const unique_p<T> &right) noexcept {
		return bool_t(right.value_ == nullptr);
	}

	[[nodiscard]] friend bool_t operator!=(const unique_p<T> &left, nullptr_t) noexcept {
		return bool_t(left.value_ != nullptr);
	}

	[[nodiscard]] friend bool_t operator!=(nullptr_t, const unique_p<T> &right) noexcept {
		return bool_t(right.value_ != nullptr);
	}
};

} // namespace scpp
